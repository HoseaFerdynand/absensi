<template>
    <div class="w-full flex flex-col items-center">
        <video id="video" autoplay playsinline class="rounded-lg border w-80 h-60"></video>
        <canvas id="overlay" class="absolute"></canvas>

        <button @click="startScan"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded">
            Start Face Scan
        </button>

        <!-- Status message -->
        <p class="mt-4 text-lg font-bold" :class="statusColor">
            {{ statusMessage }}
        </p>
        <TabelMahasiswa />
    </div>
</template>

<script setup>
import * as faceapi from 'face-api.js'
import { ref } from 'vue'
import axios from 'axios'
import TabelMahasiswa from '@/views/tables/tabel-mahasiswa.vue'

const video = ref(null)
const statusMessage = ref("Waiting to start...")
const statusColor = ref("text-gray-600")

let intervalLoop = null

let liveDescriptor = null

async function startScan() {
    statusMessage.value = "Loading models..."
    statusColor.value = "text-gray-600"

    await loadModels()
    await startCamera()
    detectLoop()
}

async function loadModels() {
    const modelUrl = '/models'
    await faceapi.nets.ssdMobilenetv1.loadFromUri(modelUrl)
    await faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl)
    await faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl)
}

async function startCamera() {
    const stream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: false
    })
    video.value = document.getElementById('video')
    video.value.srcObject = stream
}

async function detectLoop() {
    const overlay = document.getElementById('overlay')
    const ctx = overlay.getContext('2d')

    intervalLoop = setInterval(async () => {
        const box = video.value.getBoundingClientRect()
        overlay.width = box.width
        overlay.height = box.height

        // detect face
        const detection = await faceapi
            .detectSingleFace(video.value)
            .withFaceLandmarks()
            .withFaceDescriptor()

        ctx.clearRect(0, 0, overlay.width, overlay.height)

        if (!detection) {
            statusMessage.value = "Scanning..."
            statusColor.value = "text-blue-500"
            return
        }

        // Draw box
        const resized = faceapi.resizeResults(detection, {
            width: overlay.width,
            height: overlay.height
        })

        faceapi.draw.drawDetections(overlay, resized)

        // SUCCESS: live face found
        statusMessage.value = "Face Detected ✓"
        statusColor.value = "text-green-500"

        liveDescriptor = detection.descriptor

        clearInterval(intervalLoop)

        // now match the student
        matchStudent()
    }, 200)
}

async function matchStudent() {
    statusMessage.value = "Matching with database..."
    statusColor.value = "text-yellow-500"

    const res = await axios.post("/face/identify", {
        descriptor: Array.from(liveDescriptor)
    })

    if (!res.data || !res.data.match) {
        statusMessage.value = "❌ No Match Found"
        statusColor.value = "text-red-500"
        return
    }

    statusMessage.value = `MATCH FOUND: ${res.data.nama} ✓`
    statusColor.value = "text-green-600"
}

</script>
