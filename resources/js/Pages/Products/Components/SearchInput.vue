<script setup>
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { router, useForm } from "@inertiajs/vue3";

const form = useForm({
    searchKey: "",
});

function search() {
    router.get(route("search", form.searchKey), form);
}
</script>

<template>
    <form @submit.prevent="search">
        <!-- <InputLabel class="text-xl"> Product or Category name </InputLabel> -->
        <div class="flex justify-center items-center my-3">
            <TextInput
                id="searchKey"
                v-model="form.searchKey"
                name="searchKey"
                type="text"
                autofocus
                required
                class="p-1 block w-4/5 rounded-e-none"
                placeholder="Type the name of the product you are looking for:"
            >
            </TextInput>
            <PrimaryButton class="w-1/5 py-3 rounded-s-none"> Search </PrimaryButton>
        </div>
    </form>
</template>
