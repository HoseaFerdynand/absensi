<template>
<div class="flex items-center justify-between mb-6 gap-20">
    <button type="button" class="btn btn-primary" @click="$router.back()"> Back </button>

    <h4 class="font-semibold text-2xl mr-12">Manajemen Proyek Sistem Informasi</h4>
    <div></div>
</div>

<div class="w-full grid grid-cols-3 items-start gap-4">
    <div></div>

    <!-- Camera -->
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
        </div>

        <p class="mt-4 text-lg font-bold" :class="statusColor">
            {{ statusMessage }}
        </p>
    </div>

    <!-- Attendance Table -->
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
import Quagga from '@ericblade/quagga2'
import { ref, nextTick, onMounted } from 'vue'
import axios from 'axios'

const refs = ref([])
const attendance = ref([])

const video = ref(null)
const overlay = ref(null)

const statusMessage = ref("Initializing...")
const statusColor = ref("text-gray-600")

let intervalLoop = null
let faceMatcher = null
let referenceLoaded = false

// ---------------------------------------------------------
//  STARTUP
// ---------------------------------------------------------



async function startSystem() {
    await nextTick()

    try {
        statusMessage.value = "Loading face recognition models..."
        await loadFaceModels()

        statusMessage.value = "Loading mahasiswa..."
        await loadMahasiswa()

        statusMessage.value = "Starting camera..."
        await startCamera()

        statusMessage.value = "Starting face recognition..."
        startFaceLoop()

        statusMessage.value = "Starting barcode scanner..."
        startBarcodeScanner()

        statusMessage.value = "Ready"
        statusColor.value = "text-green-600"

    } catch (e) {
        console.error("Startup error:", e)
        statusMessage.value = "Startup error"
        statusColor.value = "text-red-600"
    }
}

// ---------------------------------------------------------
//  LOAD MAHASISWA
// ---------------------------------------------------------

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

// ---------------------------------------------------------
//  FACE RECOGNITION
// ---------------------------------------------------------

async function loadFaceModels() {
    const url = '/models'
    await faceapi.nets.tinyFaceDetector.loadFromUri(url)
    await faceapi.nets.faceRecognitionNet.loadFromUri(url)
    await faceapi.nets.faceLandmark68Net.loadFromUri(url)
}

async function startCamera() {
    await nextTick()

    let stream

    try {
        // Try rear camera first
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: "environment" },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        })
    } catch (err) {
        console.warn("Rear camera unavailable, using default camera")

        // Fallback to ANY camera (front)
        stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: false
        })
    }

    video.value.srcObject = stream

    await new Promise(resolve => {
        video.value.onloadedmetadata = () => {
            overlay.value.width = video.value.videoWidth
            overlay.value.height = video.value.videoHeight
            resolve()
        }
    })
}

async function startFaceLoop() {
    if (!referenceLoaded) {
        faceMatcher = await loadReferenceFaces()
        referenceLoaded = true
    }

    intervalLoop = setInterval(async () => {
        if (!video.value) return

        const det = await faceapi
            .detectSingleFace(
                video.value,
                new faceapi.TinyFaceDetectorOptions({
                    inputSize: 320,
                    scoreThreshold: 0.5
                })
            )
            .withFaceLandmarks()
            .withFaceDescriptor()

        if (!det) return

        clearInterval(intervalLoop)

        const best = faceMatcher.findBestMatch(det.descriptor)
        handleFaceMatch(best)

    }, 500)
}


async function handleFaceMatch(bestMatch) {
    if (bestMatch.label === "unknown") {
        startFaceLoop()
        return
    }

    const row = attendance.value.find(a => a.npm === bestMatch.label)
    if (row) row.hadir = "hadir"

    statusMessage.value = `${bestMatch.label} hadir (Face)`
    statusColor.value = "text-green-600"

    startFaceLoop()
}

async function loadReferenceFaces() {
    const labeledDescriptors = []

    for (const ref of refs.value) {
        try {
            const img = await faceapi.fetchImage(`/storage/mahasiswa/${ref.foto}`)

            const det = await faceapi
                .detectSingleFace(
                    img,
                    new faceapi.TinyFaceDetectorOptions({
                        inputSize: 320,
                        scoreThreshold: 0.5
                    })
                )
                .withFaceLandmarks()
                .withFaceDescriptor()

            if (!det) continue

            labeledDescriptors.push(
                new faceapi.LabeledFaceDescriptors(ref.npm, [det.descriptor])
            )
        } catch (e) {
            console.warn("Face reference load failed for:", ref.npm)
        }
    }

    return new faceapi.FaceMatcher(labeledDescriptors, 0.6)
}


// ---------------------------------------------------------
//  BARCODE SCANNING (CODE 128)
// ---------------------------------------------------------
function startBarcodeScanner() {
    if (!video.value) {
        console.error("Video element not ready for Quagga")
        return
    }

    Quagga.init(
        {
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: video.value,   // ✅ correct reference
                constraints: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
            locate: true,
            numOfWorkers: 0,
            decoder: {
                readers: ["code_39_reader"]   // ✅ correct symbology
            }
        },
        (err) => {
            if (err) {
                console.error("Quagga init error:", err)
                return
            }

            Quagga.onDetected(result => {
                const code = result.codeResult?.code
                if (code) handleBarcodeScan(code)
            })

            Quagga.start()
        }
    )
}




function waitVideoReady() {
    return new Promise(resolve => {
        const check = () => {
            if (
                video.value &&
                video.value.videoWidth > 0 &&
                video.value.videoHeight > 0
            ) {
                resolve()
            } else {
                requestAnimationFrame(check)
            }
        }

        check()
    })
}



function handleBarcodeScan(npm) {
    const row = attendance.value.find(a => a.npm === npm)

    if (!row) {
        statusMessage.value = `Barcode tidak dikenal`
        statusColor.value = "text-red-600"
        return
    }

    row.hadir = "hadir"

    statusMessage.value = `${npm} hadir (Barcode)`
    statusColor.value = "text-green-600"
}

onMounted(() => {
    startSystem()
})

</script>
