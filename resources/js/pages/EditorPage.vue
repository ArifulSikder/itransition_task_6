<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';

const two = { w: 80, h: 56, inputs: [{ x: 0, y: 16 }, { x: 0, y: 40 }], outputs: [{ x: 80, y: 28 }] };
const GATES = {
    input: { w: 80, h: 48, inputs: [], outputs: [{ x: 80, y: 24 }] },
    output: { w: 80, h: 48, inputs: [{ x: 0, y: 24 }], outputs: [] },
    not: { w: 72, h: 48, inputs: [{ x: 0, y: 24 }], outputs: [{ x: 72, y: 24 }] },
    and: two,
    or: two,
    xor: two,
    nor: two,
    nand: two,
};

const TOOLS = [
    { id: 'select', label: 'Select' },
    { id: 'wire', label: 'Wire' },
    { id: 'erase', label: 'Erase' },
    { id: 'input', label: 'IN' },
    { id: 'output', label: 'OUT' },
    { id: 'and', label: 'AND' },
    { id: 'or', label: 'OR' },
    { id: 'not', label: 'NOT' },
    { id: 'xor', label: 'XOR' },
    { id: 'nor', label: 'NOR' },
    { id: 'nand', label: 'NAND' },
];

function gateDef(type) {
    return GATES[type] ?? GATES.and;
}

function portWorld(node, kind, index) {
    const port = gateDef(node.type)[kind][index];

    return port ? { x: node.x + port.x, y: node.y + port.y } : null;
}

function wirePath(from, to) {
    const mid = (from.x + to.x) / 2;

    return `M ${from.x} ${from.y} C ${mid} ${from.y}, ${mid} ${to.y}, ${to.x} ${to.y}`;
}

function simulate(nodes, wires) {
    const byId = Object.fromEntries(nodes.map((node) => [node.uuid, node]));
    const incoming = {};

    for (const wire of wires) {
        incoming[wire.to_node_uuid] ??= {};
        incoming[wire.to_node_uuid][wire.to_port] = wire.from_node_uuid;
    }

    const values = {};
    const visiting = new Set();

    const read = (node, port) => {
        const source = incoming[node.uuid]?.[port];

        return source ? valueOf(source) : false;
    };

    const valueOf = (id) => {
        if (Object.hasOwn(values, id)) {
            return values[id];
        }

        if (visiting.has(id)) {
            return false;
        }

        visiting.add(id);
        const node = byId[id];
        let value = false;

        if (node) {
            if (node.type === 'input') {
                value = Boolean(node.value);
            } else if (node.type === 'not') {
                value = !read(node, 0);
            } else if (node.type === 'output') {
                value = read(node, 0);
            } else if (node.type === 'and') {
                value = read(node, 0) && read(node, 1);
            } else if (node.type === 'or') {
                value = read(node, 0) || read(node, 1);
            } else if (node.type === 'xor') {
                value = read(node, 0) !== read(node, 1);
            } else if (node.type === 'nand') {
                value = !(read(node, 0) && read(node, 1));
            } else if (node.type === 'nor') {
                value = !(read(node, 0) || read(node, 1));
            }
        }

        visiting.delete(id);
        values[id] = value;

        return value;
    };

    nodes.forEach((node) => valueOf(node.uuid));

    const wireValues = {};
    wires.forEach((wire) => {
        wireValues[wire.uuid] = Boolean(values[wire.from_node_uuid]);
    });

    return { nodes: values, wires: wireValues };
}

function hitNode(nodes, x, y) {
    for (let i = nodes.length - 1; i >= 0; i -= 1) {
        const node = nodes[i];
        const def = gateDef(node.type);

        if (x >= node.x && x <= node.x + def.w && y >= node.y && y <= node.y + def.h) {
            return node;
        }
    }

    return null;
}

function hitPort(nodes, x, y, radius = 9) {
    for (const node of nodes) {
        const def = gateDef(node.type);

        for (const [kind, ports] of [['output', def.outputs], ['input', def.inputs]]) {
            for (let index = 0; index < ports.length; index += 1) {
                const dx = x - (node.x + ports[index].x);
                const dy = y - (node.y + ports[index].y);

                if (dx * dx + dy * dy <= radius * radius) {
                    return {
                        node,
                        kind,
                        index,
                        x: node.x + ports[index].x,
                        y: node.y + ports[index].y,
                    };
                }
            }
        }
    }

    return null;
}

function snap(value, grid, enabled) {
    return enabled ? Math.round(value / grid) * grid : Math.round(value);
}

async function request(url, options = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        ...options.headers,
    };

    if (options.body !== undefined && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        options = { ...options, body: JSON.stringify(options.body) };
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });

    if (response.status === 204) {
        return null;
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload.message ?? 'Request failed.');
    }

    return payload;
}

const props = defineProps({
    boot: { type: Object, required: true },
    gridSizes: { type: Array, required: true },
    canDelete: { type: Boolean, default: false },
    deleteUrl: { type: String, default: '' },
    csrf: { type: String, required: true },
});

const circuit = reactive({ ...props.boot.circuit });
const nodes = ref([...props.boot.nodes]);
const wires = ref([...props.boot.wires]);
const presence = ref([...props.boot.presence]);
const tool = ref('select');
const selectedId = ref(null);
const selectedWireId = ref(null);
const showSettings = ref(false);
const pan = reactive({ x: 40, y: 40, scale: 1 });
const pointer = reactive({ x: 0, y: 0 });
const dragging = ref(null);
const wiringFrom = ref(null);
const panning = ref(null);
const board = ref(null);
const settings = reactive({
    name: circuit.name,
    grid_size: circuit.grid_size,
    snap: circuit.snap,
});

const values = computed(() => simulate(nodes.value, wires.value));
const routes = props.boot.routes;
const me = props.boot.me;

function toWorld(event) {
    const rect = board.value.getBoundingClientRect();

    return {
        x: (event.clientX - rect.left - pan.x) / pan.scale,
        y: (event.clientY - rect.top - pan.y) / pan.scale,
    };
}

function nodeById(id) {
    return nodes.value.find((node) => node.uuid === id);
}

async function createNode(type, x, y) {
    const node = {
        uuid: crypto.randomUUID(),
        type,
        x: snap(x, circuit.grid_size, circuit.snap),
        y: snap(y, circuit.grid_size, circuit.snap),
        value: false,
    };

    nodes.value.push(node);
    selectedId.value = node.uuid;

    try {
        const created = await request(routes.nodes, { method: 'POST', body: node });
        Object.assign(node, created);
        circuit.revision = created.revision ?? circuit.revision;
    } catch {
        nodes.value = nodes.value.filter((item) => item.uuid !== node.uuid);
    }
}

async function persistNode(node) {
    const result = await request(`${routes.nodes}/${node.uuid}`, {
        method: 'PATCH',
        body: { x: node.x, y: node.y, value: node.value, label: node.label },
    });
    circuit.revision = result.revision ?? circuit.revision;
}

async function createWire(from, to) {
    const wire = {
        uuid: crypto.randomUUID(),
        from_node_uuid: from.node.uuid,
        to_node_uuid: to.node.uuid,
        from_port: from.index,
        to_port: to.index,
    };

    wires.value = wires.value.filter((item) => !(item.to_node_uuid === wire.to_node_uuid && item.to_port === wire.to_port));
    wires.value.push(wire);

    try {
        const created = await request(routes.wires, { method: 'POST', body: wire });
        Object.assign(wire, created);
        circuit.revision = created.revision ?? circuit.revision;
    } catch {
        wires.value = wires.value.filter((item) => item.uuid !== wire.uuid);
    }
}

async function removeSelected() {
    if (selectedId.value) {
        const id = selectedId.value;
        nodes.value = nodes.value.filter((node) => node.uuid !== id);
        wires.value = wires.value.filter((wire) => wire.from_node_uuid !== id && wire.to_node_uuid !== id);
        selectedId.value = null;
        const result = await request(`${routes.nodes}/${id}`, { method: 'DELETE' });
        circuit.revision = result?.revision ?? circuit.revision;
        return;
    }

    if (selectedWireId.value) {
        const id = selectedWireId.value;
        wires.value = wires.value.filter((wire) => wire.uuid !== id);
        selectedWireId.value = null;
        const result = await request(`${routes.wires}/${id}`, { method: 'DELETE' });
        circuit.revision = result?.revision ?? circuit.revision;
    }
}

function onPointerDown(event) {
    const point = toWorld(event);
    pointer.x = point.x;
    pointer.y = point.y;
    board.value.setPointerCapture(event.pointerId);

    if (event.button === 1) {
        panning.value = { x: event.clientX - pan.x, y: event.clientY - pan.y };
        return;
    }

    const port = hitPort(nodes.value, point.x, point.y);

    if (wiringFrom.value && port?.kind === 'input') {
        createWire(wiringFrom.value, port);
        wiringFrom.value = null;
        return;
    }

    if (port?.kind === 'output' && (tool.value === 'wire' || tool.value === 'select')) {
        wiringFrom.value = port;
        return;
    }

    if (wiringFrom.value) {
        wiringFrom.value = null;
        return;
    }

    if (tool.value === 'wire') {
        return;
    }

    if (tool.value === 'erase') {
        const node = hitNode(nodes.value, point.x, point.y);
        if (node) {
            selectedId.value = node.uuid;
            selectedWireId.value = null;
            removeSelected();
            return;
        }
        const target = event.target;
        if (target instanceof SVGElement && target.dataset.wire) {
            selectedWireId.value = target.dataset.wire;
            selectedId.value = null;
            removeSelected();
        }
        return;
    }

    if (['input', 'output', 'and', 'or', 'not', 'xor', 'nor', 'nand'].includes(tool.value)) {
        if (!hitNode(nodes.value, point.x, point.y)) {
            createNode(tool.value, point.x - 10, point.y - 10);
        }
        return;
    }

    const node = hitNode(nodes.value, point.x, point.y);

    if (node) {
        selectedId.value = node.uuid;
        selectedWireId.value = null;
        dragging.value = { uuid: node.uuid, dx: point.x - node.x, dy: point.y - node.y };
        return;
    }

    if (event.target instanceof SVGElement && event.target.dataset.wire) {
        selectedWireId.value = event.target.dataset.wire;
        selectedId.value = null;
        return;
    }

    selectedId.value = null;
    selectedWireId.value = null;
    panning.value = { x: event.clientX - pan.x, y: event.clientY - pan.y };
}

function onPointerMove(event) {
    const point = toWorld(event);
    pointer.x = point.x;
    pointer.y = point.y;
    sendCursor(point.x, point.y);

    if (panning.value) {
        pan.x = event.clientX - panning.value.x;
        pan.y = event.clientY - panning.value.y;
        return;
    }

    if (dragging.value) {
        const node = nodeById(dragging.value.uuid);
        if (node) {
            node.x = snap(point.x - dragging.value.dx, circuit.grid_size, circuit.snap);
            node.y = snap(point.y - dragging.value.dy, circuit.grid_size, circuit.snap);
        }
    }
}

function onPointerUp(event) {
    if (dragging.value) {
        const node = nodeById(dragging.value.uuid);
        if (node) {
            persistNode(node);
        }
        dragging.value = null;
    }

    if (wiringFrom.value) {
        const port = hitPort(nodes.value, pointer.x, pointer.y);
        if (port?.kind === 'input') {
            createWire(wiringFrom.value, port);
            wiringFrom.value = null;
        }
    }

    panning.value = null;
}

function onDblClick(event) {
    const point = toWorld(event);
    const node = hitNode(nodes.value, point.x, point.y);

    if (node?.type === 'input') {
        node.value = !node.value;
        persistNode(node);
    }
}

function onWheel(event) {
    event.preventDefault();
    const next = Math.min(2, Math.max(0.5, pan.scale * (event.deltaY < 0 ? 1.08 : 0.92)));
    const point = toWorld(event);
    const rect = board.value.getBoundingClientRect();
    pan.x = event.clientX - rect.left - point.x * next;
    pan.y = event.clientY - rect.top - point.y * next;
    pan.scale = next;
}

let lastCursor = 0;

async function sendCursor(x, y) {
    const now = Date.now();
    if (now - lastCursor < 150) {
        return;
    }
    lastCursor = now;
    try {
        await request(routes.presence, { method: 'PATCH', body: { cursor_x: Math.round(x), cursor_y: Math.round(y) } });
    } catch {
        // ignore
    }
}

function applySnapshot(payload) {
    if (payload.circuit) {
        Object.assign(circuit, payload.circuit);
        if (document.activeElement?.id !== 'setting-name') {
            settings.name = circuit.name;
        }
        settings.grid_size = circuit.grid_size;
        settings.snap = circuit.snap;
    }

    if (payload.presence) {
        presence.value = payload.presence;
    }

    if (payload.unchanged) {
        return;
    }

    if (payload.nodes) {
        const dragId = dragging.value?.uuid;
        const local = dragId ? nodeById(dragId) : null;
        nodes.value = payload.nodes.map((node) => {
            if (local && node.uuid === local.uuid) {
                return { ...node, x: local.x, y: local.y };
            }
            return node;
        });
    }

    if (payload.wires) {
        wires.value = payload.wires;
    }

    if (payload.revision !== undefined) {
        circuit.revision = payload.revision;
    }
}

async function poll() {
    try {
        applySnapshot(await request(`${routes.sync}?revision=${circuit.revision}`));
    } catch {
        // keep local state
    }
}

async function saveSettings() {
    const payload = await request(routes.update, {
        method: 'PATCH',
        body: {
            name: settings.name,
            grid_size: Number(settings.grid_size),
            snap: settings.snap,
        },
    });
    applySnapshot({ circuit: payload, revision: payload.revision, nodes: nodes.value, wires: wires.value, presence: presence.value });
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(window.location.href);
    } catch {
        // ignore
    }
}

function onKey(event) {
    if (event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) {
        return;
    }

    const map = {
        v: 'select',
        w: 'wire',
        e: 'erase',
        1: 'input',
        2: 'output',
        3: 'and',
        4: 'or',
        5: 'not',
        6: 'xor',
        7: 'nor',
        8: 'nand',
    };

    if (map[event.key.toLowerCase()]) {
        tool.value = map[event.key.toLowerCase()];
    }

    if (event.key === 'Delete' || event.key === 'Backspace') {
        event.preventDefault();
        removeSelected();
    }

    if (event.key === 'Escape') {
        wiringFrom.value = null;
        selectedId.value = null;
        selectedWireId.value = null;
        tool.value = 'select';
    }
}

let timer;

onMounted(() => {
    timer = setInterval(poll, 400);
    window.addEventListener('keydown', onKey);
    window.addEventListener('pagehide', () => {
        fetch(routes.leave, {
            method: 'DELETE',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': props.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    });
});

onUnmounted(() => {
    clearInterval(timer);
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <div class="editor d-flex flex-column">
        <nav class="navbar bg-white border-bottom px-3 py-2">
            <div class="d-flex align-items-center gap-3 overflow-hidden">
                <a :href="routes.dashboard" class="text-decoration-none">Back</a>
                <span class="navbar-brand mb-0 text-truncate">{{ circuit.name }}</span>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span v-for="person in presence" :key="person.participant_uuid" class="small text-muted">
                    <span class="d-inline-block rounded-circle" :style="{ width: '8px', height: '8px', background: person.color }"></span>
                    {{ person.name }}
                </span>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="copyLink">Copy link</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="showSettings = !showSettings">Settings</button>
            </div>
        </nav>

        <div class="d-flex flex-grow-1 overflow-hidden">
            <div class="editor-tools border-end bg-white p-2" @pointerdown.stop>
                <div class="small text-muted mb-2">Parts</div>
                <button
                    v-for="item in TOOLS"
                    :key="item.id"
                    type="button"
                    class="btn btn-sm w-100 mb-1"
                    :class="tool === item.id ? 'btn-primary' : 'btn-light'"
                    @click="tool = item.id"
                >
                    {{ item.label }}
                </button>
            </div>

            <div class="editor-board">
                <svg
                    ref="board"
                    @pointerdown="onPointerDown"
                    @pointermove="onPointerMove"
                    @pointerup="onPointerUp"
                    @dblclick="onDblClick"
                    @wheel.prevent="onWheel"
                >
                <g :transform="`translate(${pan.x} ${pan.y}) scale(${pan.scale})`">
                    <template v-for="n in 50" :key="'v'+n">
                        <line :x1="n * circuit.grid_size" y1="0" :x2="n * circuit.grid_size" y2="2000" stroke="#dee2e6" />
                    </template>
                    <template v-for="n in 40" :key="'h'+n">
                        <line x1="0" :y1="n * circuit.grid_size" x2="2500" :y2="n * circuit.grid_size" stroke="#dee2e6" />
                    </template>

                    <path
                        v-for="wire in wires"
                        :key="wire.uuid"
                        :data-wire="wire.uuid"
                        :d="wirePath(portWorld(nodeById(wire.from_node_uuid) || { x: 0, y: 0, type: 'and' }, 'outputs', wire.from_port) || { x: 0, y: 0 }, portWorld(nodeById(wire.to_node_uuid) || { x: 0, y: 0, type: 'and' }, 'inputs', wire.to_port) || { x: 0, y: 0 })"
                        fill="none"
                        :stroke="values.wires[wire.uuid] ? '#198754' : '#6c757d'"
                        :stroke-width="selectedWireId === wire.uuid ? 3 : 2"
                    />

                    <path
                        v-if="wiringFrom"
                        :d="wirePath(wiringFrom, pointer)"
                        fill="none"
                        stroke="#0d6efd"
                        stroke-width="2"
                        stroke-dasharray="5 4"
                    />

                    <g v-for="node in nodes" :key="node.uuid" :transform="`translate(${node.x} ${node.y})`">
                        <rect
                            x="0"
                            y="0"
                            :width="gateDef(node.type).w"
                            :height="gateDef(node.type).h"
                            rx="4"
                            :fill="values.nodes[node.uuid] ? '#d1e7dd' : '#fff'"
                            :stroke="selectedId === node.uuid ? '#0d6efd' : '#495057'"
                            stroke-width="1.5"
                        />
                        <text
                            :x="gateDef(node.type).w / 2"
                            :y="gateDef(node.type).h / 2 + 4"
                            text-anchor="middle"
                            font-size="11"
                            fill="#212529"
                        >{{ node.label || node.type.toUpperCase() }}</text>
                        <circle
                            v-for="(port, index) in gateDef(node.type).inputs"
                            :key="'in'+index"
                            :cx="port.x"
                            :cy="port.y"
                            r="4"
                            fill="#fff"
                            stroke="#0d6efd"
                        />
                        <circle
                            v-for="(port, index) in gateDef(node.type).outputs"
                            :key="'out'+index"
                            :cx="port.x"
                            :cy="port.y"
                            r="4"
                            :fill="values.nodes[node.uuid] ? '#198754' : '#fff'"
                            stroke="#0d6efd"
                        />
                    </g>

                    <g
                        v-for="person in presence.filter((p) => p.participant_uuid !== me.uuid && p.cursor_x != null)"
                        :key="'c'+person.participant_uuid"
                        :transform="`translate(${person.cursor_x} ${person.cursor_y})`"
                    >
                        <circle r="4" :fill="person.color" />
                        <text x="8" y="4" font-size="11" :fill="person.color">{{ person.name }}</text>
                    </g>
                </g>
                </svg>
            </div>

            <div v-if="showSettings" class="editor-settings border-start bg-white p-3" @pointerdown.stop>
                <h2 class="h6">Settings</h2>
                <div class="mb-3">
                    <label class="form-label" for="setting-name">Name</label>
                    <input id="setting-name" v-model="settings.name" maxlength="80" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label">Grid size</label>
                    <select v-model.number="settings.grid_size" class="form-select form-select-sm">
                        <option v-for="size in gridSizes" :key="size" :value="size">{{ size }}px</option>
                    </select>
                </div>
                <div class="form-check mb-3">
                    <input id="setting-snap" v-model="settings.snap" type="checkbox" class="form-check-input">
                    <label class="form-check-label" for="setting-snap">Snap to grid</label>
                </div>
                <button type="button" class="btn btn-primary btn-sm" @click="saveSettings">Save</button>
                <form v-if="canDelete" :action="deleteUrl" method="POST" class="mt-4" @submit="(e) => { if (!confirm('Delete this circuit?')) e.preventDefault(); }">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-link btn-sm text-danger p-0">Delete circuit</button>
                </form>
                <p class="small text-muted mt-4 mb-0">Double-click an input to toggle. Drag empty space to pan.</p>
            </div>
        </div>
    </div>
</template>
