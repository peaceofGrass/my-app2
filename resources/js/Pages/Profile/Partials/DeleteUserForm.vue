<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-stone-900 dark:text-stone-100">
                계정 삭제
            </h2>

            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                계정을 삭제하면 모든 리소스와 데이터가 영구적으로 삭제됩니다.
                삭제하기 전에 보관하려는 데이터나 정보를 먼저 내려받아 주세요.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">계정 삭제</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2
                    class="text-lg font-medium text-stone-900 dark:text-stone-100"
                >
                    정말 계정을 삭제하시겠습니까?
                </h2>

                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    계정을 삭제하면 모든 리소스와 데이터가 영구적으로
                    삭제됩니다. 계정을 영구적으로 삭제하려면 비밀번호를 입력해
                    주세요.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="비밀번호"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="비밀번호"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        취소
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        계정 삭제
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
