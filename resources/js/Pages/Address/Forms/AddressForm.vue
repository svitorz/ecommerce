<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import FormSection from "@/Components/FormSection.vue";
import { useForm } from "@inertiajs/vue3";
import { defineProps } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ActionMessage from "@/Components/ActionMessage.vue";

const props = defineProps({
    address: Object,
    show: {
        type: Boolean,
        required: false,
        default: true,
    },
});

const form = useForm({
    country: props.address?.country || "",
    state: props.address?.state || "",
    city: props.address?.city || "",
    street: props.address?.street || "",
    zip_code: props.address?.zip_code || "",
    number: props.address?.number || "",
});

const saveAddress = () => {
    form.post(route("address.store"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="saveAddress">
        <template #title> Address Information </template>
        <template #description>
            {{ show ? "Update your account's profile" : "Confirm your" }}
            address.
        </template>
        <template #form :class="{ 'flex justify-center items-center': !show }">
            <div class="col-span-6 sm:col-span-4">
                <!-- zip code -->
                <InputLabel for="zip_code" value="Zip code" />
                <TextInput
                    id="zip_code"
                    v-model="form.zip_code"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="postal-code"
                />
                <InputError :message="form.errors.zip_code" class="mt-2" />
            </div>
            <!-- country -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="country" value="Country" />
                <TextInput
                    id="country"
                    v-model="form.country"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="country-name"
                />
                <InputError :message="form.errors.country" class="mt-2" />
            </div>
            <!-- state -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="state" value="State" />
                <TextInput
                    id="state"
                    v-model="form.state"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="address-level1"
                />
                <InputError :message="form.errors.state" class="mt-2" />
            </div>
            <!-- city -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="city" value="City" />
                <TextInput
                    id="city"
                    v-model="form.city"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="address-level2"
                />
                <InputError :message="form.errors.city" class="mt-2" />
            </div>
            <!-- street -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="street" value="Street" />
                <TextInput
                    id="street"
                    v-model="form.street"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="address-line1"
                />
                <InputError :message="form.errors.street" class="mt-2" />
            </div>
            <!-- number -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="number" value="Number" />
                <TextInput
                    id="number"
                    v-model="form.number"
                    type="number"
                    class="mt-1 block w-full"
                    required
                    autocomplete="address-line2"
                />
                <InputError :message="form.errors.number" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3"> Saved. </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
