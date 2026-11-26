<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import AddressForm from "@/Pages/Address/Forms/AddressForm.vue";
import PaymentForm from "@/Pages/Orders/Form/PaymentForm.vue";
const step = ref(1);
const props = defineProps({
    amount: Number,
    order: Object,
});
</script>

<template>
    <AppLayout title="Checkout">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Confirm your order.
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6 h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-2.5 rounded-full bg-primary-600"
                        :style="{ width: step === 1 ? '50%' : '90%' }"
                    ></div>
                </div>

                <div v-if="step === 1">
                    <AddressForm :show="false" :address="$page.props.auth.user.address" />
                </div>
                <div v-if="step === 2">
                    <PaymentForm :amount="amount" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton v-if="step > 1" @click="step--"> Go back </SecondaryButton>
                    <PrimaryButton v-if="step === 1" @click="step++"> Next </PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
