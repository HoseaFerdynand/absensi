<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mahasiswa;

class FaceController extends Controller
{
    public function identify(Request $req)
    {
        $target = $req->descriptor; // incoming 128-d array

        $students = Mahasiswa::all();

        $bestMatch = null;
        $bestDistance = 999;

        foreach ($students as $mhs) {
            $stored = json_decode($mhs->descriptor, true); // stored descriptor

            if (!$stored) continue;

            // Euclidean distance
            $dist = 0;
            for ($i = 0; $i < 128; $i++) {
                $dist += pow($target[$i] - $stored[$i], 2);
            }
            $dist = sqrt($dist);

            if ($dist < $bestDistance) {
                $bestDistance = $dist;
                $bestMatch = $mhs;
            }
        }

        if ($bestDistance > 0.45) {
            return ['match' => false];
        }

        return [
            'match' => true,
            'nama' => $bestMatch->nama,
            'npm'  => $bestMatch->npm,
            'distance' => $bestDistance
        ];
    }

    public function references()
{
    $students = Mahasiswa::all();

    $refs = [];

    foreach ($students as $mhs) {

        // must have descriptor + photo
        if (!$mhs->descriptor || !$mhs->foto) {
            continue;
        }

        // decode descriptor
        $desc = json_decode($mhs->descriptor, true);

        // must be valid 128-d array
        if (!is_array($desc) || count($desc) !== 128) {
            continue;
        }

        // build full image URL
        $imgUrl = asset('storage/mahasiswa/' . $mhs->foto);

        $refs[] = [
            'nama' => $mhs->nama,
            'descriptor' => $desc,
            'img_url' => $imgUrl,
        ];
    }

    return response()->json($refs);
}

public function identified(Request $request)
{
    return response()->json([
        'status' => 'ok',
        'name' => $request->name
    ]);
}

}
