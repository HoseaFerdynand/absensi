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
}
