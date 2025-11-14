<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Daftar Mahasiswa</h1>

        <table class="min-w-full border">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border px-3 py-2">NPM</th>
                    <th class="border px-3 py-2">Nama</th>
                    <th class="border px-3 py-2">Kelas</th>
                    <th class="border px-3 py-2">Prodi</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="m in mahasiswa" :key="m.id" class="border">
                    <td class="border px-3 py-2">{{ m.npm }}</td>
                    <td class="border px-3 py-2">{{ m.nama }}</td>
                    <td class="border px-3 py-2">{{ m.kelas }}</td>
                    <td class="border px-3 py-2">{{ m.prodi }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const mahasiswa = ref([])

onMounted(async () => {
    const result = await axios.get('/api/mahasiswa')
    mahasiswa.value = result.data
})
</script>
