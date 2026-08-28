<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

async function request(url) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
    });

    if (!response.ok) {
        throw new Error('Request failed.');
    }

    return response.json();
}

const props = defineProps({
    participantName: { type: String, required: true },
    circuits: { type: Array, required: true },
    gridSizes: { type: Array, required: true },
    defaultGridSize: { type: Number, required: true },
    storeUrl: { type: String, required: true },
    listUrl: { type: String, required: true },
    leaveUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const circuits = ref(props.circuits);
const showForm = ref(false);
let timer;

onMounted(() => {
    timer = setInterval(async () => {
        try {
            circuits.value = await request(props.listUrl);
        } catch {
            // keep the last list
        }
    }, 2000);
});

onUnmounted(() => {
    clearInterval(timer);
});
</script>

<template>
    <nav class="navbar bg-white border-bottom px-3">
        <span class="navbar-brand mb-0 h1">Circuits</span>
        <div class="d-flex align-items-center gap-3">
            <span>{{ participantName }}</span>
            <form :action="leaveUrl" method="POST">
                <input type="hidden" name="_token" :value="csrf">
                <button type="submit" class="btn btn-link btn-sm text-secondary p-0">Leave</button>
            </form>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Open circuits</h1>
            <button type="button" class="btn btn-primary" @click="showForm = !showForm">New circuit</button>
        </div>

        <form v-if="showForm" :action="storeUrl" method="POST" class="card mb-4" style="max-width: 28rem;">
            <input type="hidden" name="_token" :value="csrf">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" required maxlength="80" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Grid size</label>
                    <select name="grid_size" class="form-select">
                        <option v-for="size in gridSizes" :key="size" :value="size" :selected="size === defaultGridSize">
                            {{ size }}px
                        </option>
                    </select>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="snap" value="0">
                    <input id="snap" type="checkbox" name="snap" value="1" class="form-check-input" checked>
                    <label class="form-check-label" for="snap">Snap to grid</label>
                </div>
                <button type="submit" class="btn btn-primary me-2">Create</button>
                <button type="button" class="btn btn-link" @click="showForm = false">Cancel</button>
            </div>
        </form>

        <div v-if="circuits.length === 0" class="alert alert-light border">No circuits yet.</div>

        <div v-else class="list-group">
            <div v-for="circuit in circuits" :key="circuit.uuid" class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div>
                    <div>{{ circuit.name }}</div>
                    <div class="small text-muted">
                        {{ circuit.grid_size }}px grid
                        · {{ circuit.node_count ?? 0 }} parts
                        · {{ circuit.live_count ?? 0 }} online
                        <span v-if="(circuit.presences ?? []).length">
                            — {{ circuit.presences.map((p) => p.name).join(', ') }}
                        </span>
                    </div>
                </div>
                <a :href="circuit.url" class="btn btn-outline-primary btn-sm">Connect</a>
            </div>
        </div>
    </main>
</template>
