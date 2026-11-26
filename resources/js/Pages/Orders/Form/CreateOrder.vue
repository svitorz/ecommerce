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
    <AppLayout>
        <template #header>
            <h2 class="text-white">Confirm your order.</h2>
        </template>
        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
            <div
                class="bg-blue-600 h-2.5 rounded-full"
                :style="step === 1 ? 'width: 50%;' : 'width: 90%;'"
            ></div>
            <div class="p-5 block" v-if="step === 1">
                <AddressForm :show="false" :address="$page.props.auth.user.address" />
            </div>
            <div class="p-5 block" v-if="step === 2">
                <PaymentForm :amount="amount" />
            </div>
            <div class="float-end p-5">
                <SecondaryButton @click="step--" v-if="step > 1"> Go back </SecondaryButton>

                <PrimaryButton v-if="step == 1" @click="step++"> Next </PrimaryButton>
            </div>
        </div>
    </AppLayout>
</template>
