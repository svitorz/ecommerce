<script setup>
import DangerButton from "@/Components/DangerButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import { defineProps } from "vue";
import ToastManager from "@/Components/ToastManager.vue";
import { ref } from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { router } from "@inertiajs/vue3";
const props = defineProps({
    status: {
        type: String,
        required: true,
        validator: (value) =>
            ["pending_payment", "transporting", "completed", "canceled"].includes(value),
    },
    order_id: {
        type: String,
        required: true,
    },
    order_date: {
        type: Date,
        required: true,
    },
    payd_at: {
        type: Date,
        required: true,
    },
});

const stepMapping = {
    pending_payment: ["order_placed"],
    transporting: ["order_placed", "payment", "shipping"],
    completed: ["order_placed", "payment", "shipping", "delivered"],
    canceled: [],
};

const isStepActive = (step) => {
    return stepMapping[props.status]?.includes(step) || false;
};

const getTextColorClass = (step) => {
    return isStepActive(step)
        ? "text-primary-700 dark:text-primary-500"
        : "text-gray-500 dark:text-gray-400";
};
const formattedDate = (order_date) => {
    return new Date(order_date).toLocaleString("en-US", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });
};

const confirmingDelete = ref(false);
const toastRef = ref(null);
const confirmDelete = () => {
    confirmingDelete.value = true;
};

const closeModal = () => {
    confirmingDelete.value = false;
};

const cancelOrder = async (id) => {
    if (props.status === "canceled") {
        toastRef.value.showToast("Order is already canceled!", "error");
        return;
    }
    try {
        await router.visit(route("orders.update", id), {
            method: "PUT",
            data: {
                status: "canceled",
            },
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toastRef.value.showToast("Order canceled!", "success");
                props.status.value = "canceled";
            },
            onError: () => {
                toastRef.value.showToast("Error to cancel order.", "error");
            },
            onFinish: () => {
                closeModal();
            },
        });
    } catch (error) {
        toastRef.value.showToast("Unexpected error", "error");
        console.error(error);
    }
};
</script>
<template>
    <ol class="relative ms-3 border-s border-gray-200 dark:border-gray-700 border-b-8">
        <!-- Delivery -->
        <li class="mb-10 ms-6">
            <span
                class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full ring-8 ring-white dark:ring-gray-800"
                :class="
                    isStepActive('delivered')
                        ? 'bg-primary-100 dark:bg-primary-900'
                        : 'bg-gray-100 dark:bg-gray-700'
                "
            >
                <svg
                    v-if="isStepActive('delivered')"
                    class="h-4 w-4 text-primary-700"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 11.917 9.724 16.5 19 7.5"
                    />
                </svg>
                <svg
                    v-else
                    class="h-4 w-4 text-gray-500"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="m4 12 8-8 8 8M6 10.5V19a1 1 0 0 0 1 1h3v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h3a1 1 0 0 0 1-1v-8.5"
                    />
                </svg>
            </span>
            <h4 class="mb-0.5 text-base font-semibold" :class="getTextColorClass('delivered')">
                Delivery
            </h4>
            <p class="text-sm" :class="getTextColorClass('delivered')">Order delivered</p>
        </li>

        <!-- Shipping -->
        <li class="mb-10 ms-6">
            <span
                class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full ring-8 ring-white dark:ring-gray-800"
                :class="
                    isStepActive('shipping')
                        ? 'bg-primary-100 dark:bg-primary-900'
                        : 'bg-gray-100 dark:bg-gray-700'
                "
            >
                <svg
                    v-if="isStepActive('shipping')"
                    class="h-4 w-4 text-primary-700"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 11.917 9.724 16.5 19 7.5"
                    />
                </svg>
                <svg
                    v-else
                    class="h-4 w-4 text-gray-500"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 7h6l2 4m-8-4v8m0-8V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v9h2m8 0H9m4 0h2m4 0h2v-4m0 0h-5m3.5 5.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-10 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"
                    />
                </svg>
            </span>
            <h4 class="mb-0.5 text-base font-semibold" :class="getTextColorClass('shipping')">
                Shipping
            </h4>
            <p class="text-sm" :class="getTextColorClass('shipping')">Order in transit</p>
        </li>

        <!-- Payment -->
        <li class="mb-10 ms-6">
            <span
                class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full ring-8 ring-white dark:ring-gray-800"
                :class="
                    isStepActive('payment')
                        ? 'bg-primary-100 dark:bg-primary-900'
                        : 'bg-gray-100 dark:bg-gray-700'
                "
            >
                <svg
                    v-if="isStepActive('payment')"
                    class="h-4 w-4 text-primary-700"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 11.917 9.724 16.5 19 7.5"
                    />
                </svg>
                <svg
                    v-else
                    class="h-4 w-4 text-gray-500"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5.5 10h13v4h-13v-4Z"
                    />
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2"
                    />
                </svg>
            </span>
            <h4 class="mb-0.5 text-base font-semibold" :class="getTextColorClass('payment')">
                Payment
            </h4>
            <p class="text-sm" :class="getTextColorClass('payment')">
                Payment confirmed
                <br />
                {{ formattedDate(payd_at) }}
            </p>
        </li>

        <!-- Order Placed/Canceled -->
        <li class="ms-6">
            <span
                class="absolute -start-3 flex h-6 w-6 items-center justify-center rounded-full ring-8 ring-white dark:ring-gray-800"
                :class="[
                    status === 'canceled'
                        ? 'bg-red-100 dark:bg-red-900'
                        : isStepActive('order_placed')
                          ? 'bg-primary-100 dark:bg-primary-900'
                          : 'bg-gray-100 dark:bg-gray-700',
                ]"
            >
                <svg
                    v-if="status === 'canceled'"
                    class="h-4 w-4 text-red-700"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
                <svg
                    v-else-if="isStepActive('order_placed')"
                    class="h-4 w-4 text-primary-700"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 11.917 9.724 16.5 19 7.5"
                    />
                </svg>
                <svg
                    v-else
                    class="h-4 w-4 text-gray-500"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3.5 5.5 7 9l7-7"
                    />
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 15v5m0 0 3-3m-3 3-3-3"
                    />
                </svg>
            </span>
            <h4
                class="mb-0.5 text-base font-semibold"
                :class="
                    status === 'canceled'
                        ? 'text-red-700 dark:text-red-500'
                        : getTextColorClass('order_placed')
                "
            >
                {{ status === "canceled" ? "Order Canceled" : "Order Placed" }}
            </h4>
            <p
                class="text-sm"
                :class="
                    status === 'canceled'
                        ? 'text-red-700 dark:text-red-500'
                        : getTextColorClass('order_placed')
                "
            >
                Order #{{ order_id }}
                <Br />
                {{ formattedDate(order_date) }}
            </p>
        </li>
    </ol>
    <DangerButton
        v-if="status != 'completed'"
        @click="confirmDelete"
        :type="button"
        class="w-full"
        :disabled="status === 'canceled'"
    >
        {{ status === "canceled" ? "Already canceled." : "Cancel order." }}
    </DangerButton>
    <DialogModal :show="confirmingDelete" @close="closeModal">
        <template #title> Cancel this order? </template>

        <template #content> This action is irreversible! </template>

        <template #footer>
            <SecondaryButton :type="button" @click="closeModal"> Go back </SecondaryButton>

            <DangerButton :type="button" class="ms-3" @click="cancelOrder(order_id)">
                Confirm
            </DangerButton>
        </template>
    </DialogModal>
    <ToastManager ref="toastRef" />
</template>
