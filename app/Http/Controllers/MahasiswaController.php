<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MahasiswaController extends Controller
{
    public function index()
    {
        return Mahasiswa::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'npm' => 'required|unique:mahasiswa',
            'nama' => 'required',
            'kelas' => 'required',
            'prodi' => 'required',
            'foto' => 'nullable|file|image'
        ]);

        if ($request->hasFile('foto')) {
            $filename = $request->npm . '.' . $request->foto->extension();
            $request->foto->storeAs('public/mahasiswa', $filename);
            $validated['foto'] = $filename;
        }

        return Mahasiswa::create($validated);
    }


    public function update(Request $request, $id)
    {
        $mhs = Mahasiswa::findOrFail($id);

        $validated = $request->validate([
            'npm' => 'required|unique:mahasiswa,npm,' . $id,
            'nama' => 'required',
            'kelas' => 'required',
            'prodi' => 'required',
            'foto' => 'nullable|file|image',
        ]);

        if ($request->hasFile('foto')) {
            $filename = $request->npm . '.' . $request->foto->extension();
            $request->foto->storeAs('public/mahasiswa', $filename);
            $validated['foto'] = $filename;
        }

        $mhs->update($validated);

        return $mhs;
    }


    public function destroy($id)
    {
        $mhs = Mahasiswa::findOrFail($id);
        $mhs->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function saveDescriptor(Request $req, $id)
    {
        $mhs = Mahasiswa::findOrFail($id);

        $mhs->descriptor = json_encode($req->descriptor);
        $mhs->save();

        return ['status' => 'saved'];
    }

    public function getFaces()
    {
        $faces = Mahasiswa::select('id', 'npm', 'nama', 'foto')
            ->get()
            ->map(function ($item) {
                $item->foto = asset('storage/mahasiswa/' . $item->foto);
                return $item;
            });

        return response()->json($faces);
    }
}

