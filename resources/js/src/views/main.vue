<template>

  <div class="w-full grid grid-cols-3 items-start gap-4">
    <!-- CAMERA + STATUS -->
    <div class="col-span-1 relative flex flex-col items-center">

      <!-- VIDEO + CANVAS WRAPPER -->
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


      <button
        @click="startScan"
        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded"
      >
        Start Face Scan
      </button>

      <p class="mt-4 text-lg font-bold" :class="statusColor">
        {{ statusMessage }}
      </p>
    </div>

    <!-- TABLE -->
    <TabelMahasiswa class="col-span-2" />
    <!-- STATIC ATTENDANCE TABLE -->
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
import { ref, nextTick } from 'vue'
import axios from 'axios'
import TabelMahasiswa from '@/views/tables/tabel-mahasiswa.vue'
axios.get('/api/mahasiswa');

const refs = ref([]); // this stores mahasiswa faces
const attendance = ref([
  {
    npm: "23412943",
    nama: "Hansen",
    kelas: "SI-5A",
    prodi: "Sistem Informasi",
    hadir: "tidak"
  },
  {
    npm: "23412944",
    nama: "Hosea Ferdynand",
    kelas: "SI-5A",
    prodi: "Sistem Informasi",
    hadir: "tidak"
  }
]);



const video = ref(null)
const overlay = ref(null)

const statusMessage = ref("Waiting to start...")
const statusColor = ref("text-gray-600")

let intervalLoop = null
let liveDescriptor = null

async function loadMahasiswa() {
  const res = await axios.get("/api/mahasiswa"); // adjust route if needed
  refs.value = res.data;   // store the array
  console.log("Mahasiswa loaded:", refs.value);
}

async function startScan() {
  console.log("DEBUG: BUTTON CLICKED")

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
  await nextTick();   // ensures <video> is mounted

  const stream = await navigator.mediaDevices.getUserMedia({
    video: true,
    audio: false
  });

  video.value.srcObject = stream;

  // The FIX — wait for real resolution
  video.value.onloadedmetadata = () => {
    overlay.value.width = video.value.videoWidth;
    overlay.value.height = video.value.videoHeight;
    detectLoop();            // now safe to start detection
  };
}

async function detectLoop() {
  console.log("DEBUG: Entering detectLoop()");

  const canvas = overlay.value;
  const ctx = canvas.getContext("2d");

  // IMPORTANT FIX
  faceMatcher = await loadReferenceFaces();

  intervalLoop = setInterval(async () => {
    if (!video.value) return;

    // ctx.setTransform(-1, 0, 0, 1, canvas.width, 0);
    // ctx.clearRect(0, 0, canvas.width, canvas.height);

    const det = await faceapi
      .detectSingleFace(video.value)
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!det) return;

    clearInterval(intervalLoop);

    const resized = faceapi.resizeResults(det, {
      width: canvas.width,
      height: canvas.height
    });

    faceapi.draw.drawDetections(canvas, resized);
    faceapi.draw.drawFaceLandmarks(canvas, resized);

    const best = faceMatcher.findBestMatch(det.descriptor);

    new faceapi.draw.DrawTextField(
      [best.toString()],
      resized.detection.box.bottomLeft
    ).draw(canvas);

    matchStudent(best);
  }, 600);
}


async function matchStudent(bestMatch) {
  if (bestMatch.label === "unknown") {
    statusMessage.value = "No match";
    statusColor.value = "text-red-500";
    return;
  }

  // update the static attendance table
  const row = attendance.value.find(a => a.npm === bestMatch.label);
  if (row) {
    row.hadir = "hadir";
  }

  statusMessage.value = `${bestMatch.label} hadir`;
  statusColor.value = "text-green-600";
}


let faceMatcher = null;

const loadReferenceFaces = async () => {
  console.log("Loading reference faces...");

  const labeledDescriptors = [];

  for (const ref of refs.value) {

    try {
      const imgUrl = `/storage/mahasiswa/${ref.foto}`;
      console.log("Loading image:", imgUrl);

      const img = await faceapi.fetchImage(imgUrl);

      const detection = await faceapi
        .detectSingleFace(img)
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (!detection) {
        console.warn(`❌ No face detected in: ${imgUrl}`);
        continue;
      }

      console.log(`✅ Face detected for ${ref.npm}`);

      labeledDescriptors.push(
        new faceapi.LabeledFaceDescriptors(ref.npm, [detection.descriptor])
      );

    } catch (err) {
      console.error(`❌ Error loading ${ref.foto}`, err);
    }
  }

  if (labeledDescriptors.length === 0) {
    throw new Error("No valid reference faces loaded!");
  }

  console.log("✔ All reference faces loaded:", labeledDescriptors);
  return new faceapi.FaceMatcher(labeledDescriptors, 0.6);
};



</script>
