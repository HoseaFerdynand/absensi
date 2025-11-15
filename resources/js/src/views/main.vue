<template>
<div class="flex items-center justify-between mb-6 gap-20">
    <button type="button" class="btn btn-primary" @click="$router.back()" > Back </button>

    <h4 class="font-semibold text-2xl mr-12">Manajemen Proyek Sistem Informasi</h4>
    <div></div>
</div>


<div class="w-full grid grid-cols-3 items-start gap-4">
    <div></div>
    <div class="col-span-1 relative flex flex-col items-center">
    <div class="relative w-80 h-60">
    <video
        ref="video"
        autoplay
        playsinline
        class="rounded-lg border w-full h-full object-cover"
    ></video>

    <canvas
        ref="overlay"
        class="absolute inset-0 w-full h-full pointer-events-none z-10"
    ></canvas>
    <div></div>
</div>

    <p class="mt-4 text-lg font-bold" :class="statusColor">
        {{ statusMessage }}
    </p>
    </div>

    <!-- <TabelMahasiswa class="col-span-2" /> -->

<div class="col-span-3 mt-6">
<h2 class="text-xl font-bold mb-4">Absensi Hari Ini</h2>

<table class="min-w-full border">
    <thead class="bg-gray-100">
    <tr>
        <th class="p-2 border">NPM</th>
        <th class="p-2 border">Nama</th>
        <th class="p-2 border">Kelas</th>
        <th class="p-2 border">Prodi</th>
        <th class="p-2 border">Hadir</th>
    </tr>
    </thead>

    <tbody>
    <tr v-for="row in attendance" :key="row.npm">
        <td class="p-2 border">{{ row.npm }}</td>
        <td class="p-2 border">{{ row.nama }}</td>
        <td class="p-2 border">{{ row.kelas }}</td>
        <td class="p-2 border">{{ row.prodi }}</td>
        <td class="p-2 border font-bold">
        <span :class="row.hadir === 'hadir' ? 'text-green-600' : 'text-red-600'">
            {{ row.hadir }}
        </span>
        </td>
    </tr>
    </tbody>
</table>
</div>

</div>
</template>

<script setup>
import * as faceapi from 'face-api.js'
import { ref, nextTick, onMounted } from 'vue'
import axios from 'axios'
// import TabelMahasiswa from '@/views/tables/tabel-mahasiswa.vue'

const refs = ref([])
const attendance = ref([])


const video = ref(null)
const overlay = ref(null)

const statusMessage = ref("Initializing...")
const statusColor = ref("text-gray-600")

let intervalLoop = null
let faceMatcher = null
let referenceLoaded = false

onMounted(() => {
startScan()
})

async function loadMahasiswa() {
    const res = await axios.get("/api/mahasiswa")

    const sorted = res.data.sort((a, b) => a.npm.localeCompare(b.npm))

    refs.value = sorted

    attendance.value = sorted.map(m => ({
        npm: m.npm,
        nama: m.nama,
        kelas: m.kelas,
        prodi: m.prodi,
        hadir: "tidak"
    }))
}



async function startScan() {
await nextTick()

statusMessage.value = "Loading models..."
await loadModels()

statusMessage.value = "Loading mahasiswa..."
await loadMahasiswa()

statusMessage.value = "Starting camera..."
await startCamera()
}

async function loadModels() {
const modelUrl = '/models'
await faceapi.nets.ssdMobilenetv1.loadFromUri(modelUrl)
await faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl)
await faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl)
}

async function startCamera() {
await nextTick()

const stream = await navigator.mediaDevices.getUserMedia({
    video: true,
    audio: false
})

video.value.srcObject = stream

video.value.onloadedmetadata = () => {
    overlay.value.width = video.value.videoWidth
    overlay.value.height = video.value.videoHeight
    detectLoop()
}
}

async function detectLoop() {
const canvas = overlay.value

if (!referenceLoaded) {
    faceMatcher = await loadReferenceFaces()
    referenceLoaded = true
}

intervalLoop = setInterval(async () => {
    if (!video.value) return

    const det = await faceapi
    .detectSingleFace(video.value)
    .withFaceLandmarks()
    .withFaceDescriptor()

    if (!det) return

    clearInterval(intervalLoop)

    const resized = faceapi.resizeResults(det, {
    width: canvas.width,
    height: canvas.height
    })

    const best = faceMatcher.findBestMatch(det.descriptor)

    matchStudent(best)
}, 500)
}

async function matchStudent(bestMatch) {
if (bestMatch.label === "unknown") {
    detectLoop()
    return
}

const row = attendance.value.find(a => a.npm === bestMatch.label)
if (row) row.hadir = "hadir"

statusMessage.value = `${bestMatch.label} hadir`
statusColor.value = "text-green-600"

detectLoop()
}

const loadReferenceFaces = async () => {
const labeledDescriptors = []

for (const ref of refs.value) {
    try {
    const imgUrl = `/storage/mahasiswa/${ref.foto}`
    const img = await faceapi.fetchImage(imgUrl)

    const detection = await faceapi
        .detectSingleFace(img)
        .withFaceLandmarks()
        .withFaceDescriptor()

        if (!detection) continue

        labeledDescriptors.push(
        new faceapi.LabeledFaceDescriptors(ref.npm, [detection.descriptor])
        )

    } catch {}
    }

    return new faceapi.FaceMatcher(labeledDescriptors, 0.6)
}
</script>
