<script setup>
import { ref } from "vue";

const show = ref(false);
const message = ref("");
const type = ref("success");

const toastClasses = {
    success: "bg-green-500",
    error: "bg-red-500",
    warning: "bg-yellow-500",
    info: "bg-blue-500",
};

const showToast = (newMessage, newType = "success") => {
    message.value = newMessage;
    type.value = newType;
    show.value = true;

    setTimeout(() => {
        show.value = false;
    }, 3000);
};

defineExpose({
    showToast,
});
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform translate-y-2 opacity-0"
    >
        <div
            v-show="show"
            :class="[
                'fixed bottom-4 right-4 text-white px-6 py-3 rounded-lg shadow-lg z-50',
                toastClasses[type],
            ]"
        >
            {{ message }}
        </div>
    </transition>
</template>
