<script setup>
import DropdownLink from "@/Components/DropdownLink.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DialogModal from "@/Components/DialogModal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Link } from "@inertiajs/vue3";
defineProps({
    category: {
        type: Object,
        required: true,
    },
    isAdmin: Boolean,
});
const form = useForm({
    deleteCategoryId: 0,
});

const confirmingDelete = ref(false);
const confirmDelete = () => {
    confirmingDelete.value = true;
};

const closeModal = () => {
    confirmingDelete.value = false;
};

const deleteCategory = (deleteCategoryId) => {
    form.delete(route("categories.destroy", { category: deleteCategoryId }), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <div v-if="category">
        <div
            class="block max-w-sm my-4 p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700"
        >
            <!-- dropdown to edit and exclude the category buttons-->
            <Dropdown align="right" width="48" v-if="isAdmin">
                <template #trigger>
                    <span class="inline-flex rounded-md float-end">
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150"
                        >
                            <svg
                                class="ms-2 -me-0.5 size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                />
                            </svg>
                        </button>
                    </span>
                </template>

                <template #content>
                    <!-- content -->
                    <DropdownLink :href="route('categories.edit', category.id)">
                        Edit Category
                    </DropdownLink>
                    <div class="border-t border-gray-200 dark:border-gray-600" />
                    <DropdownLink as="button" @click="confirmDelete">
                        Delete Category
                    </DropdownLink>
                </template>
            </Dropdown>
            <!-- end dropdown -->
            <!-- Confirmation Dialog -->
            <DialogModal :show="confirmingDelete" @close="closeModal">
                <template #title> Delete this Category? </template>

                <template #content> This action is irreversible! </template>

                <template #footer>
                    <SecondaryButton :type="button" @click="closeModal"> Cancel </SecondaryButton>

                    <DangerButton
                        :type="button"
                        class="ms-3"
                        :class="{
                            'opacity-25': form.processing,
                        }"
                        :disabled="form.processing"
                        @click="deleteCategory(category.id)"
                    >
                        Delete
                    </DangerButton>
                </template>
            </DialogModal>
            <!--  Confirmation Dialog -->
            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ category?.name }}
            </h5>

            <p class="font-normal text-gray-700 dark:text-gray-400 pb-3">
                {{ category?.description }}
            </p>

            <Link
                v-if="category?.id"
                :href="
                    route('categories.products', {
                        category: category?.id,
                    })
                "
            >
                <PrimaryButton :type="button"> See related Products </PrimaryButton>
            </Link>
        </div>
    </div>
</template>
