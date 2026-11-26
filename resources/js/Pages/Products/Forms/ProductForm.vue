<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { useForm } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ActionMessage from "@/Components/ActionMessage.vue";

const props = defineProps({
    categories: Array,
    product: Array,
    method: { type: String },
    route: { type: String },
});

const form = useForm({
    name: props.product?.name || "",
    description: props.product?.description || "",
    price: Number(props.product?.price || 0).toFixed(2),
    stock: Number(props.product?.stock || 0),
    category_id: props.product?.category_id || "",
    image: null,
});

function submit() {
    if (props.method === "PUT") {
        form.transform((data) => ({ ...data, _method: "PUT" })).post(props.route, {
            forceFormData: true,
        });
        return;
    }

    form.post(props.route, { forceFormData: true });
}
</script>

<template>
    <AppLayout title="Create Product">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Creating a new Product
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="d-flex justify-content-center align-items-center m-5">
                        <form @submit.prevent="submit">
                            <div class="flex gap-4">
                                <div class="w-1/2">
                                    <InputLabel value="Product name" for="name" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="name"
                                        placeholder="Ex: Smartphone."
                                        class="mt-1 w-full h-12"
                                        required
                                        autofocus
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>
                                <div class="w-1/2">
                                    <InputLabel value="Product description" for="description" />
                                    <textarea
                                        class="w-full h-36 mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm py-2"
                                        name="description"
                                        id="description"
                                        v-model="form.description"
                                        rows="4"
                                        cols="4"
                                        placeholder="Ex: Smart, Beautiful and customizable."
                                    ></textarea>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="w-1/2">
                                    <InputLabel
                                        value="Product price (with two decimal places)"
                                        for="price"
                                    />
                                    <TextInput
                                        id="price"
                                        v-model="form.price"
                                        type="number"
                                        min="0.0"
                                        step="any"
                                        placeholder="Ex: 1500.99"
                                        class="mt-1 w-full h-12"
                                        required
                                        autofocus
                                    />
                                    <InputError class="mt-2" :message="form.errors.price" />
                                </div>
                                <div class="w-1/2">
                                    <InputLabel value="Product stock" for="stock" />
                                    <TextInput
                                        id="stock"
                                        v-model="form.stock"
                                        type="number"
                                        min="0"
                                        step="1"
                                        placeholder="Ex: 1."
                                        class="mt-1 w-full h-12"
                                        required
                                        autofocus
                                    />
                                    <InputError class="mt-2" :message="form.errors.stock" />
                                </div>
                            </div>
                            <div class="w-full py-2">
                                <select
                                    v-model="form.category_id"
                                    name="category_id"
                                    id="categories"
                                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm py-2"
                                >
                                    <option value="" selected>Select one</option>
                                    <option
                                        v-for="category in categories"
                                        :value="category.id"
                                        :key="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.category_id" />
                            </div>
                            <div class="w-full py-2">
                                <label
                                    for="image"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Product image (JPG, PNG or WebP, up to 2 MB)</label
                                >
                                <input
                                    id="image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300"
                                    @change="form.image = $event.target.files[0] ?? null"
                                />
                                <InputError class="mt-2" :message="form.errors.image" />
                            </div>
                            <ActionMessage> Saved. </ActionMessage>
                            <PrimaryButton class="my-4 float-end"> Save </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
