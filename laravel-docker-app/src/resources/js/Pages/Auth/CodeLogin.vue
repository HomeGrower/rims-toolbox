<template>
    <GuestLayout>
        <Head title="RIMS Pre-Installation" />

        <div class="mb-4 text-center">
            <h2 class="text-2xl font-bold text-gray-900">RIMS PRE-INSTALLATION SUITE</h2>
            <p class="mt-2 text-sm text-gray-600">Enter your access code to view your project dashboard</p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="access_code" value="Access Code" />

                <TextInput
                    id="access_code"
                    type="text"
                    class="mt-1 block w-full uppercase"
                    v-model="form.access_code"
                    required
                    autofocus
                    autocomplete="off"
                    placeholder="Enter your 8-character code"
                    maxlength="8"
                />

                <InputError class="mt-2" :message="form.errors.access_code" />
            </div>

            <div class="flex items-center justify-center mt-6">
                <PrimaryButton class="w-full justify-center" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Access Dashboard
                </PrimaryButton>
            </div>
        </form>

    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    access_code: '',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        access_code: data.access_code.toUpperCase()
    })).post(route('code.submit'), {
        onFinish: () => {
            if (form.errors.access_code) {
                form.reset('access_code');
            }
        },
    });
};
</script>