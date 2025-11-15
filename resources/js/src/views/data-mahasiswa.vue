<template>
    <div>

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h5 class="text-xl font-semibold">Manajemen Data Mahasiswa</h5>
        </div>

        <!-- CRUD FORM -->
        <div class="panel mb-6">
            <h5 class="font-semibold text-2xl mb-4">
                {{ isEdit ? "Edit Mahasiswa" : "Tambah Mahasiswa" }}
            </h5>

            <form @submit.prevent="save">
                <div class="grid md:grid-cols-2 gap-6">

                    <!-- LEFT SIDE (FORM FIELDS) -->
                    <div class="space-y-4">
                        <div>
                            <label>NPM</label>
                            <input v-model="form.npm" type="text" class="form-input" required />
                        </div>

                        <div>
                            <label>Nama</label>
                            <input v-model="form.nama" type="text" class="form-input" required />
                        </div>

                        <div>
                            <label>Kelas</label>
                            <input v-model="form.kelas" type="text" class="form-input" required />
                        </div>

                        <div>
                            <label>Prodi</label>
                            <input v-model="form.prodi" type="text" class="form-input" required />
                        </div>

                        <div class="flex justify-start gap-2 mt-6">
                        <button
                            type="button"
                            class="btn btn-outline"
                            v-if="isEdit"
                            @click="resetForm"
                        >
                            Cancel Edit
                        </button>

                        <button type="submit" class="btn btn-primary">
                            {{ isEdit ? "Update" : "Simpan" }}
                        </button>
                    </div>
                    </div>


                    <!-- RIGHT SIDE (IMAGE UPLOADER) -->
                    <div class="space-y-2">
                    <label>Foto</label>

                    <div
                        class="custom-file-container"
                        data-upload-id="fotoUpload"
                    >

                        <label class="custom-file-container__custom-file flex items-center gap-2">

                            <!-- FILE INPUT -->
                            <input
                                type="file"
                                class="custom-file-container__custom-file__custom-file-input"
                                accept="image/*"
                            />
                            <input type="hidden" name="MAX_FILE_SIZE" value="20000000" />

                            <span class="custom-file-container__custom-file__custom-file-control"></span>

                            <!-- CLEAR BUTTON NEXT TO UPLOAD -->
                            <a href="javascript:void(0)"
                            class="custom-file-container__image-clear ml-2 text-xl leading-none"
                            title="Hapus Foto">
                            ×
                            </a>

                        </label>

                        <div class="custom-file-container__image-preview mt-2"></div>
                    </div>
                </div>


                </div>



            </form>
        </div>

        <!-- TABLE -->
        <div class="panel">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>NPM</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Prodi</th>
                            <th>Foto</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="mhs in mahasiswa" :key="mhs.id">
                            <td>{{ mhs.npm }}</td>
                            <td>{{ mhs.nama }}</td>
                            <td>{{ mhs.kelas }}</td>
                            <td>{{ mhs.prodi }}</td>
                            <td>
                                <img
                                    v-if="mhs.foto"
                                    :src="`/storage/mahasiswa/${mhs.foto}?t=${Date.now()}`"
                                    class="w-12 h-12 rounded object-cover"
                                />

                            </td>

                            <td class="flex gap-2 justify-center">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary flex items-center gap-1"
                                @click="editRow(mhs)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    width="16" height="16" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    viewBox="0 0 24 24">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                                </svg>
                            </button>

                            <!-- Delete Button -->
                            <button
                                type="button"
                                class="btn btn-sm btn-danger flex items-center gap-1"
                                @click="deleteRow(mhs.id)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    width="16" height="16" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    viewBox="0 0 24 24">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14H6L5 6"/>
                                    <path d="M10 11v6"/>
                                    <path d="M14 11v6"/>
                                    <path d="M9 6V4h6v2"/>
                                </svg>
                            </button>

                        </td>

                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import axios from "axios";
import FileUploadWithPreview from "file-upload-with-preview";


let uploader = null;

const mahasiswa = ref([]);

const form = ref({
    id: null,
    npm: "",
    nama: "",
    kelas: "",
    prodi: "",
    foto: null
});

const isEdit = computed(() => form.value.id !== null);

// ==========================================
// INIT VRISTO FILE UPLOADER
// ==========================================
async function initUploader(existingImage = null) {
    await nextTick();

    const container = document.querySelector(
        ".custom-file-container[data-upload-id='fotoUpload']"
    );
    if (!container) return;

    if (uploader) {
        try {
            uploader.resetPreviewPanel();
        } catch (e) {}
    }

    uploader = new FileUploadWithPreview("fotoUpload", {
        images: {
            baseImage: "/assets/images/file-preview.svg",
            backgroundImage: "",
        },
    });

    if (existingImage) {
        const url = `/storage/mahasiswa/${existingImage}?t=${Date.now()}`;
        uploader.addImagesFromPath(url);
    }
}


// ==========================================
// FETCH
// ==========================================
async function getMahasiswa() {
    const res = await axios.get("/api/mahasiswa");
    mahasiswa.value = res.data.sort((a, b) => a.npm.localeCompare(b.npm));
}

// ==========================================
// SAVE
// ==========================================
async function save() {
    const fd = new FormData();

    fd.append("npm", form.value.npm);
    fd.append("nama", form.value.nama);
    fd.append("kelas", form.value.kelas);
    fd.append("prodi", form.value.prodi);

    if (uploader?.cachedFileArray?.length > 0) {
        fd.append("foto", uploader.cachedFileArray[0]);
    }

    if (isEdit.value) {
        await axios.post(`/api/mahasiswa/${form.value.id}`, fd, {
            headers: { "X-HTTP-Method-Override": "PUT" }
        });
    } else {
        await axios.post("/api/mahasiswa", fd);
    }

    resetForm();
    getMahasiswa();
}

// ==========================================
// EDIT
// ==========================================
async function editRow(row) {
    form.value = {
        id: row.id,
        npm: row.npm,
        nama: row.nama,
        kelas: row.kelas,
        prodi: row.prodi,
        foto: null
    };

    await initUploader(row.foto);
}

// ==========================================
// DELETE
// ==========================================
async function deleteRow(id) {
    if (!confirm("Hapus data ini?")) return;
    await axios.delete(`/api/mahasiswa/${id}`);
    getMahasiswa();
}

// ==========================================
// RESET FORM
// ==========================================
async function resetForm() {
    form.value = {
        id: null,
        npm: "",
        nama: "",
        kelas: "",
        prodi: "",
        foto: null
    };

    if (uploader) {
        try {
            uploader.clearImagePreview();
            uploader.resetPreviewPanel();
        } catch (e) {}
    }

    await initUploader();
}
const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    form.foto = file; // store file in form object
};

// ==========================================
// ON MOUNT
// ==========================================
onMounted(async () => {
    await getMahasiswa();
    await initUploader();
});

</script>
