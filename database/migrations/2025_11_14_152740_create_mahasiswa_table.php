<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->string('npm')->unique();
            $table->string('nama');
            $table->string('kelas');
            $table->string('prodi');
            $table->string('foto')->nullable();
            $table->timestamps();
        });
        DB::table('mahasiswa')->insert([
            [
                'npm'   => '23412943',
                'nama'  => 'Hansen',
                'kelas' => 'SI-5A',
                'prodi' => 'Sistem Informasi',
                'foto'  => '23412943.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'npm'   => '23412944',
                'nama'  => 'Hosea Ferdynand',
                'kelas' => 'SI-5A',
                'prodi' => 'Sistem Informasi',
                'foto'  => '23412944.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'npm'   => '23412945',
                'nama'  => 'Jastyn Yosanli',
                'kelas' => 'SI-5A',
                'prodi' => 'Sistem Informasi',
                'foto'  => '23412945.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
