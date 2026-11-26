<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";
import OrderProgress from "./Components/OrderProgress.vue";
import { ref } from "vue";
const props = defineProps({
    orders: Array,
});
const localOrders = ref([...props.orders]);
const originalData = ref([...props.orders]);
const filters = ref(["pending_payment", "transporting", "completed", "canceled"]);
const currentFilter = ref("");

const filterData = (filter) => {
    currentFilter.value = filter;
    if (filter === "") {
        localOrders.value = [...originalData.value];
    } else {
        localOrders.value = originalData.value.filter((order) => order.status === filter);
    }
};
</script>

<template>
    <AppLayout title="Orders">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                See your last order
            </h2>
        </template>

        <div class="py-2 dark:text-white">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" v-if="localOrders.length > 0">
                <div v-for="filter in filters" class="flex items-center p-1">
                    <input
                        @click="filterData(filter)"
                        type="radio"
                        :id="filter"
                        :value="filter"
                        name="filter"
                        v-model="currentFilter"
                    />
                    <label :for="filter">{{
                        filter.charAt(0).toUpperCase() + filter.slice(1).replace("_", " ")
                    }}</label>
                </div>
                <section
                    class="bg-white py-8 antialiased dark:bg-gray-900 md:py-16"
                    v-for="order in localOrders"
                >
                    <div class="mx-auto max-w-screen-xl px-4 2xl:px-0">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">
                            Track the delivery of order #{{ order.id }}
                        </h2>

                        <div class="mt-6 sm:mt-8 lg:flex lg:gap-8" v-if="order.products">
                            <div
                                class="w-full divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700 lg:max-w-xl xl:max-w-2xl"
                            >
                                <div class="space-y-4 p-6" v-for="product in order.products">
                                    <div class="flex items-center gap-6">
                                        <a href="#" class="h-14 w-14 shrink-0">
                                            <img
                                                class="h-full w-full dark:hidden"
                                                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front.svg"
                                                alt="imac image"
                                            />
                                            <img
                                                class="hidden h-full w-full dark:block"
                                                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front-dark.svg"
                                                alt="imac image"
                                            />
                                        </a>

                                        <Link
                                            :href="route('products.show', product.id)"
                                            class="min-w-0 flex-1 font-medium text-gray-900 hover:underline dark:text-white"
                                        >
                                            {{ product.name }}
                                        </Link>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <p
                                            class="text-sm font-normal text-gray-500 dark:text-gray-400"
                                        >
                                            <span class="font-medium text-gray-900 dark:text-white"
                                                >Product ID:</span
                                            >
                                            {{ product.id }}
                                        </p>

                                        <div class="flex items-center justify-end gap-4">
                                            <p
                                                class="text-base font-normal text-gray-900 dark:text-white"
                                            >
                                                {{ product.pivot_quantity }}
                                            </p>

                                            <p
                                                class="text-xl font-bold leading-tight text-gray-900 dark:text-white"
                                            >
                                                ${{ product.price }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4 bg-gray-50 p-6 dark:bg-gray-800">
                                    <div class="space-y-2">
                                        <dl class="flex items-center justify-between gap-4">
                                            <dt
                                                class="font-normal text-gray-500 dark:text-gray-400"
                                            >
                                                Original price
                                            </dt>
                                            <dd class="font-medium text-gray-900 dark:text-white">
                                                ${{ order.subtotal }}
                                            </dd>
                                        </dl>
                                    </div>

                                    <dl
                                        class="flex items-center justify-between gap-4 border-t border-gray-200 pt-2 dark:border-gray-700"
                                    >
                                        <dt class="text-lg font-bold text-gray-900 dark:text-white">
                                            Total
                                        </dt>
                                        <dd class="text-lg font-bold text-gray-900 dark:text-white">
                                            ${{ order.subtotal }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>

                            <div class="mt-6 grow sm:mt-8 lg:mt-0">
                                <div
                                    class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <OrderProgress
                                        :status="order.status"
                                        :order_date="order.created_at"
                                        :order_id="order.id"
                                        :payd_at="order.payments.created_at"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div v-else>
                <h1 class="text-center dark:text-white">No matches found.</h1>
            </div>
        </div>
    </AppLayout>
</template>
