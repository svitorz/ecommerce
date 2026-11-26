<script setup>
import { defineProps, ref, reactive, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Dropdown from "@/Components/Dropdown.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import DialogModal from "@/Components/DialogModal.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CartButton from "../Cart/Components/CartButton.vue";
import ToastManager from "@/Components/ToastManager.vue";
import FavoriteButton from "../Favorites/FavoriteButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { usePage } from "@inertiajs/vue3";
import StarRating from "@/Components/StarRating.vue";
import InputError from "@/Components/InputError.vue";
import ProductRatings from "@/Components/ProductRatings.vue";
const page = usePage();
const props = defineProps({
    product: Object,
    isAdmin: Boolean,
});

const localProduct = reactive({ ...props.product });

watch(
    () => props.product,
    (newVal) => {
        Object.assign(localProduct, newVal);
    },
    { deep: true, immediate: true },
);
function updateProductQuantity(quantity) {
    localProduct.quantity = quantity;
}
const toastRef = ref(null);

// functions
const form = useForm({
    deleteProductId: 0,
});
const ratingForm = useForm({
    rating: 0,
    feedback: "",
});
const confirmingDelete = ref(false);
const confirmDelete = () => {
    confirmingDelete.value = true;
};

const closeModal = () => {
    confirmingDelete.value = false;
};

const deleteProduct = (deleteProductId) => {
    form.delete(route("products.destroy", deleteProductId), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// Estado para controlar o modal
const isModalOpen = ref(false);

const openRatingModal = () => {
    isModalOpen.value = true;
};

const closeRatingModal = () => {
    isModalOpen.value = false;
};
const iconColor = ref("text-gray-400");

// Função para alterar a cor ao passar o mouse
const handleMouseEnter = () => {
    iconColor.value = "text-yellow-400";
};
const handleMouseLeave = () => {
    iconColor.value = "text-gray-400";
};

// Função chamada ao clicar no botão
const setRating = (rating) => {
    ratingForm.rating = rating;
    console.log(ratingForm.rating);
};

const submitRating = () => {
    try {
        ratingForm.post(
            route("rating.store", {
                product: localProduct.id,
            }),
            {
                onSuccess: () => {
                    toastRef.value.showToast("Feedback sended!", "success");
                    closeRatingModal();
                },
            },
        );
    } catch (error) {
        toastRef.value.showToast("Unexpected error", "error");
        console.log(error);
    }
};

const calculateRating = () => {
    if (!localProduct.ratings || localProduct.ratings.length === 0) {
        return 0;
    }

    const validRatings = localProduct.ratings
        .map((rating) => Number(rating.rating))
        .filter((rating) => !isNaN(rating));

    // Se não houver ratings válidos após a filtragem
    if (validRatings.length === 0) {
        return 0;
    }

    const sum = validRatings.reduce((acc, rating) => acc + rating, 0);
    return Number((sum / validRatings.length).toFixed(1));
};
</script>

<template>
    <AppLayout :title="localProduct?.name">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ localProduct.category.name }}
            </h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex items-center justify-center mb-4 md:mb-8">
                    <div
                        class="m-2 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <!-- delete and edit dropdown -->
                        <!-- dropdown to edit and exclude the category buttons-->
                        <Dropdown align="right" width="48" v-if="isAdmin">
                            <template #trigger>
                                <span class="inline-flex rounded-md float-end">
                                    <button
                                        type="button"
                                        class="m-5 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150"
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
                                <DropdownLink :href="route('products.edit', localProduct.id)">
                                    Edit Product
                                </DropdownLink>
                                <div class="border-t border-gray-200 dark:border-gray-600" />
                                <DropdownLink as="button" @click="confirmDelete">
                                    Delete Product
                                </DropdownLink>
                            </template>
                        </Dropdown>
                        <!-- end dropdown -->
                        <!-- Confirmation Dialog -->
                        <DialogModal :show="confirmingDelete" @close="closeModal">
                            <template #title> Delete this Product? </template>

                            <template #content>
                                This action is irreversible!
                                <InputError :message="form.errors.product" class="mt-2" />
                            </template>

                            <template #footer>
                                <SecondaryButton :type="button" @click="closeModal">
                                    Cancel
                                </SecondaryButton>

                                <DangerButton
                                    :type="button"
                                    class="ms-3"
                                    :class="{
                                        'opacity-25': form.processing,
                                    }"
                                    :disabled="form.processing"
                                    @click="deleteProduct(localProduct.id)"
                                >
                                    Delete
                                </DangerButton>
                            </template>
                        </DialogModal>
                        <!--  Confirmation Dialog -->
                        <section
                            class="p-8 bg-white dark:text-white md:py-16 dark:bg-gray-900 antialiased"
                        >
                            <div class="max-w-screen-xl px-4 mx-auto 2xl:px-0">
                                <div class="lg:grid lg:grid-cols-2 lg:gap-8 xl:gap-16">
                                    <div class="shrink-0 max-w-md lg:max-w-lg mx-auto">
                                        <img
                                            class="w-full dark:hidden ms-1"
                                            src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front.svg"
                                            alt=""
                                        />
                                        <img
                                            class="w-full hidden dark:block ms-1"
                                            src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front-dark.svg"
                                            alt=""
                                        />
                                    </div>

                                    <div class="mt-6 sm:mt-8 lg:mt-0">
                                        <h1
                                            class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white"
                                        >
                                            <!-- product name -->
                                            {{ localProduct.name }}
                                        </h1>
                                        <div class="mt-4 sm:items-center sm:gap-4 sm:flex">
                                            <p
                                                class="text-2xl font-extrabold text-gray-900 sm:text-3xl dark:text-white"
                                            >
                                                ${{ localProduct.price }}
                                            </p>

                                            <div class="flex items-center gap-2 mt-2 sm:mt-0">
                                                <button class="flex" @click="openRatingModal()">
                                                    <div class="flex items-center gap-1">
                                                        <StarRating :rating="calculateRating()" />
                                                    </div>
                                                    <p
                                                        class="text-sm font-medium leading-none text-gray-500 dark:text-gray-400"
                                                    >
                                                        <!-- rating -->
                                                        ({{ calculateRating() ?? 0 }})
                                                    </p>
                                                    <a
                                                        href="#"
                                                        class="text-sm font-medium leading-none text-gray-900 underline hover:no-underline dark:text-white"
                                                    >
                                                        {{ localProduct.ratings.length }}
                                                        Review(s)
                                                    </a>
                                                    <DialogModal
                                                        :show="isModalOpen"
                                                        @close="closeRatingModal()"
                                                    >
                                                        <template #title> Review Product </template>

                                                        <template #content>
                                                            <InputLabel
                                                                for="feedback"
                                                                value="Product feedback"
                                                            />
                                                            <TextInput
                                                                v-model="ratingForm.feedback"
                                                                id="feedback"
                                                                type="text"
                                                                placeholder="Ex: Awesome product."
                                                                class="mt-1 w-full h-12"
                                                                required
                                                                autofocus
                                                            />
                                                            <button
                                                                v-for="star in 5"
                                                                :key="star"
                                                                @click="setRating(star)"
                                                                :class="{
                                                                    'text-yellow-400':
                                                                        star <= ratingForm.rating,
                                                                    'text-gray-400':
                                                                        star > ratingForm.rating,
                                                                }"
                                                                class="text-2xl my-2 focus:outline-none"
                                                                aria-label="Rate {{ star }}"
                                                            >
                                                                <svg
                                                                    class="w-6 h-6 cursor-pointer transition-transform transform hover:scale-125"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    fill="currentColor"
                                                                    viewBox="0 0 22 20"
                                                                    :class="iconColor"
                                                                    @mouseenter="handleMouseEnter"
                                                                    @mouseleave="handleMouseLeave"
                                                                    aria-hidden="true"
                                                                >
                                                                    <path
                                                                        d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z"
                                                                    />
                                                                </svg>
                                                            </button>
                                                        </template>

                                                        <template #footer>
                                                            <SecondaryButton
                                                                :type="button"
                                                                @click="closeRatingModal"
                                                            >
                                                                Cancel
                                                            </SecondaryButton>

                                                            <PrimaryButton
                                                                :type="button"
                                                                @click="submitRating()"
                                                                class="ms-3"
                                                                :class="{
                                                                    'opacity-25': form.processing,
                                                                }"
                                                                :disabled="form.processing"
                                                            >
                                                                Send
                                                            </PrimaryButton>
                                                        </template>
                                                    </DialogModal>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-6 sm:gap-4 sm:items-center sm:flex sm:mt-8">
                                            <p
                                                class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                            >
                                                {{ localProduct.stock }}
                                                avaliable.
                                            </p>
                                            <div>
                                                <InputLabel for="quantity" value="Quantity" />
                                                <TextInput
                                                    id="quantity"
                                                    v-model="localProduct.quantity"
                                                    type="number"
                                                    class="mt-1 w-full h-12"
                                                    :max="localProduct.stock"
                                                    required
                                                    autofocus
                                                />
                                            </div>
                                        </div>
                                        <div class="mt-6 sm:gap-4 sm:items-center sm:flex sm:mt-8">
                                            <FavoriteButton :product="localProduct" />

                                            <CartButton
                                                @click="updateProductQuantity"
                                                :product="localProduct"
                                            />
                                        </div>

                                        <hr
                                            class="my-6 md:my-8 border-gray-200 dark:border-gray-800"
                                        />

                                        <p class="mb-6 text-gray-500 dark:text-gray-400">
                                            {{ localProduct.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
                <ProductRatings :ratings="localProduct.ratings" />
            </div>
        </div>
        <ToastManager ref="toastRef" />
    </AppLayout>
</template>
