<script setup>
import ProductCard from "@/Pages/Products/Components/ProductCard.vue";
import { ref, onMounted, watch } from "vue";

const props = defineProps({
    products: {
        type: Array,
        required: true,
        default: () => [], // Add default empty array
    },
});

const currentSlide = ref(0);
const totalSlides = ref(0);
const itemsPerSlide = 3;

// Function to calculate total slides
const calculateTotalSlides = () => {
    if (!props.products?.length) {
        totalSlides.value = 0;
        return;
    }
    totalSlides.value = Math.ceil(props.products.length / itemsPerSlide);
};

// Watch for changes in products
watch(
    () => props.products,
    (newProducts) => {
        calculateTotalSlides();
        // Reset current slide if it's out of bounds
        if (currentSlide.value >= totalSlides.value) {
            currentSlide.value = 0;
        }
    },
    { immediate: true },
);

onMounted(() => {
    calculateTotalSlides();
});

const nextSlide = () => {
    if (totalSlides.value === 0) return;
    currentSlide.value = (currentSlide.value + 1) % totalSlides.value;
};

const prevSlide = () => {
    if (totalSlides.value === 0) return;
    currentSlide.value = currentSlide.value === 0 ? totalSlides.value - 1 : currentSlide.value - 1;
};

const goToSlide = (slideIndex) => {
    if (slideIndex < totalSlides.value) {
        currentSlide.value = slideIndex;
    }
};

const getSlideProducts = (slideIndex) => {
    if (!props.products?.length) return [];
    const start = slideIndex * itemsPerSlide;
    return props.products.slice(start, start + itemsPerSlide);
};
</script>

<template>
    <div
        v-if="props.products?.length"
        id="default-carousel"
        class="relative w-full"
        data-carousel="slide"
    >
        <!-- Carousel wrapper -->
        <div class="relative h-[600px] overflow-hidden rounded-lg">
            <!-- Carousel items -->
            <div
                v-for="slideIndex in totalSlides"
                :key="slideIndex"
                class="duration-700 ease-in-out absolute w-full h-full transition-transform"
                :class="{ hidden: currentSlide !== slideIndex - 1 }"
            >
                <div class="flex gap-4 justify-center items-center h-full">
                    <ProductCard
                        v-for="product in getSlideProducts(slideIndex - 1)"
                        :key="product.id"
                        :product="product"
                        class="w-1/3 px-2"
                    />
                </div>
            </div>
        </div>

        <!-- Only show navigation if there are multiple slides -->
        <template v-if="totalSlides > 1">
            <!-- Carousel indicators -->
            <div
                class="absolute z-30 flex -translate-x-1/2 bottom-5 left-1/2 space-x-3 rtl:space-x-reverse"
            >
                <button
                    v-for="slideIndex in totalSlides"
                    :key="slideIndex"
                    type="button"
                    class="w-3 h-3 rounded-full"
                    :class="{
                        'bg-white': currentSlide === slideIndex - 1,
                        'bg-white/50': currentSlide !== slideIndex - 1,
                    }"
                    :aria-current="currentSlide === slideIndex - 1"
                    :aria-label="`Slide ${slideIndex}`"
                    @click="goToSlide(slideIndex - 1)"
                ></button>
            </div>

            <!-- Previous button -->
            <button
                type="button"
                class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none"
                @click="prevSlide"
            >
                <span
                    class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none"
                >
                    <svg
                        class="w-4 h-4 text-white dark:text-gray-800 rtl:rotate-180"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 6 10"
                    >
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 1 1 5l4 4"
                        />
                    </svg>
                    <span class="sr-only">Previous</span>
                </span>
            </button>

            <!-- Next button -->
            <button
                type="button"
                class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none"
                @click="nextSlide"
            >
                <span
                    class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none"
                >
                    <svg
                        class="w-4 h-4 text-white dark:text-gray-800 rtl:rotate-180"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 6 10"
                    >
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m1 9 4-4-4-4"
                        />
                    </svg>
                    <span class="sr-only">Next</span>
                </span>
            </button>
        </template>
    </div>
    <div v-else class="flex justify-center items-center h-[600px] bg-gray-100 rounded-lg">
        <p class="text-gray-500">No products available</p>
    </div>
</template>
