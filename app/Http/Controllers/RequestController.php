<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('hashcheck');
    }

    public function getDataKIP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_pendaftaran' => 'required',
            'kode_akses' => 'required',
        ]);


        if($validator->fails())
        {
            return response()->json(['status' => 'bad request', 'reason' => $validator->errors(), 'time' => Carbon::now()], 400);
        }


        $res = Http::get('https://kip-kuliah.kemdikbud.go.id/siswa/auth/login');
        $cookies = $res->cookies()->toArray();
        $cookieJar = "";
        $arr = explode("</div>", $res->body());
        $div = explode("<div", $arr[1]);
        $newLine = explode("\n", $div[2]);
        $kutip2 = explode('"', $newLine[2]);
        $csrfToken = $kutip2[5];

        foreach ($cookies as $ck)
        {
            $cookieJar .= $ck["Name"]."=".$ck["Value"]."; ";
        }

        $res2 = Http::asForm()->withHeaders([
            'Cookie' => $cookieJar
        ])->post('https://kip-kuliah.kemdikbud.go.id/siswa/auth/login', [
            '_token' => $csrfToken,
            'no_pendaftaran' => $request->no_pendaftaran,
            'kode_akses' => $request->kode_akses,
        ]);
        $cardBody = explode("</tr>", $res2->body());

        if(!array_key_exists(12,$cardBody))
        {
            return response()->json(['status' => 'unauthorized', 'reason' => 'Nomor Pendaftaran atau Kode Akses salah', 'time' => Carbon::now()], 401);
        }


        $DOM = new \DOMDocument();
        libxml_use_internal_errors(true);
        $DOM->loadHTML($res2->body());
//        $DOM->loadHTMLFile(asset('/html/file.html'));
        libxml_clear_errors();
        $selector = new \DOMXPath($DOM);
        $selector2 = new \DOMXPath($DOM);

        $result = $selector->query('//table');
        $datamhs = $this->getData($result);

        $result2 = $selector2->query("//li[@class='user-header bg-primary']");
        $akunmhs = $this->getName($result2);


        return response()->json(['data_mahasiswa' => $datamhs, 'akun_mahasiswa' => $akunmhs]);

//        $header = $DOM->getElementsByTagName('th');
//        $detail = $DOM->getElementsByTagName('td');
//
//        foreach ($header as $nodeHeader)
//        {
//            $dataTable[] = trim($nodeHeader->textContent);
//        }
//
//        foreach ($detail as $nodeDetail)
//        {
//            $detailTable = trim($nodeDetail->textContent);
//        }



//        $trNIM = $cardBody[12];
//        $trStatus = $cardBody[16];
//        $trRemove = explode("tr", $trStatus);
//        $tdRemove = explode("td", $trRemove[1]);
//        $liRemove = explode("li", $tdRemove[1]);

    }

    function getName($result)
    {
        $line = "";
        foreach ($result as $node)
        {
            $line = $node->textContent;
        }

        $arraynode = explode("\n", $line);

        $namaArray = explode(' ', $arraynode[3]);

        $nama_lengkap = "";

        foreach ($namaArray as $index=>$value)
        {
            if($value != "")
            {
                $nama_lengkap .= " ".$value;
            }
        }

        return [
            'nama_lengkap' => $nama_lengkap,
            'no_pendaftaran' => str_replace(' ', '', $arraynode[4])
        ];
    }

    function getData($result)
    {
        $arraynode = [];

        foreach ($result as $node)
        {
            array_push($arraynode, trim($node->textContent));
        }

        $data = $arraynode[1];
        $dataArray = explode("\n", $data);

        $universitas = explode("-", $dataArray[5])[1];
        $program_studi = explode("-", $dataArray[10])[1];
        $nim = preg_replace('/\s+/', '', $dataArray[16]);
        $no_rekening = preg_replace('/\s+/', '', $dataArray[29]);

        $statusRekening =[];

        $lineLimiter = 0;
        $objectStatus = false;
        $objectName = "";
        $objectIndexArray = 0;
        for($i = 36; $i < 62; $i++)
        {
            $line = preg_replace('/\s+/', '', $dataArray[$i]);
            if(str_contains($line, 'Semester'))
            {
                $objectIndexArray++;
                array_push($statusRekening, [$line => []]);
                $objectStatus = true;
                $objectName = $line;
            }
            if($objectStatus)
            {
                if($line != "")
                {
                    $lineLimiter = 0;
                    if($line != "RiwayatProses:" && $line != $objectName) array_push($statusRekening[$objectIndexArray - 1][$objectName], $line);
                } else {
                    $lineLimiter++;
                }
            } else {
                array_push($statusRekening, $line);
            }

            if($lineLimiter > 4)
            {
                $objectStatus = false;

            }
        }

        return [
            'statusRekening' => $statusRekening,
            'universitas' => $universitas,
            'program_studi' => $program_studi,
            'nim' => $nim,
            'no_rekening' => $no_rekening];
    }
}
