<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import CategoryCard from "./Components/CategoryCard.vue";
import Pagination from "@/Components/Pagination.vue";
import { ref } from "vue";
const props = defineProps({ categories: Object, isAdmin: Boolean });

const categories = ref([...props.categories.data]);
</script>
<template>
    <AppLayout title="Category">
        <template #header>
            <div v-if="isAdmin">
                <PrimaryButton :type="button" class="float-end">
                    <a href="/categories/create"> Create New Category </a>
                </PrimaryButton>
            </div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                All Categories
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="grid grid-cols-3 m-2">
                        <div v-for="category in categories">
                            <CategoryCard :category="category" :isAdmin="isAdmin" />
                        </div>
                    </div>
                </div>
                <div class="w-full flex items-center justifiy-center">
                    <Pagination :links="props.categories.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
