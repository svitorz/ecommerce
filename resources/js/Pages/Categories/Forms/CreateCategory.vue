<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { useForm } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";

const props = defineProps({
    category: { type: [Object, null], default: null },
    method: { type: String },
    route: { type: String },
});

const form = useForm({
    name: props.category?.name || "",
    description: props.category?.description || "",
});

function submit() {
    router.visit(props.route, { method: props.method, data: form });
}
</script>
<template>
    <AppLayout title="Category">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <span v-if="!props.category"> Create Category </span>
                <span v-else> Update Category </span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="d-flex justify-content-center align-items-center m-5">
                        <form @submit.prevent="submit">
                            <!-- Category name -->
                            <InputLabel for="name" value="Category Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="name"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                            <!-- END CATEGORY NAME -->

                            <!-- Category Description -->
                            <InputLabel for="descrption" value="Category Description" />

                            <TextInput
                                id="description"
                                v-model="form.description"
                                type="description"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                            <!-- End category description -->

                            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                                <span v-if="!props.category"> Created. </span>
                                <span v-else> Updated. </span>
                            </ActionMessage>
                            <!-- Save button -->
                            <PrimaryButton
                                class="my-3 float-end"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                <span v-if="!props.category"> Create </span>
                                <span v-else> Update </span>
                            </PrimaryButton>
                            <!-- End save button -->
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
