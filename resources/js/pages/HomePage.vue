<script setup>
defineProps({
    errors: { type: Array, default: () => [] },
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    oldName: { type: String, default: '' },
});
</script>

<template>
    <div class="d-flex min-vh-100 align-items-center justify-content-center p-3">
        <form :action="storeUrl" method="POST" class="card shadow-sm" style="width: 22rem;">
            <input type="hidden" name="_token" :value="csrf">
            <div class="card-body">
                <h1 class="h4">Circuits</h1>
                <p class="text-muted small">Enter a name to join. If that name is taken, a number is added (John, John 2, ...).</p>
                <div class="mb-3">
                    <label class="form-label" for="display-name">Your name</label>
                    <input
                        id="display-name"
                        name="name"
                        type="text"
                        required
                        maxlength="32"
                        :value="oldName"
                        class="form-control"
                    >
                </div>
                <div v-for="error in errors" :key="error" class="text-danger small mb-2">{{ error }}</div>
                <button type="submit" class="btn btn-primary w-100">Continue</button>
            </div>
        </form>
    </div>
</template>
