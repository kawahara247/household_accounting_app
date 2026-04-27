<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const model = defineModel({
    type: [String, Number],
    default: '',
});

const showCalculator = ref(false);
const displayValue = ref('');
const currentOperator = ref(null);
const previousValue = ref(null);
const waitingForOperand = ref(false);
const calculatorRef = ref(null);
const inputRef = ref(null);
const calculatorStyle = ref({});
const teleportTarget = ref('body');

const formattedDisplayValue = computed(() => {
    if (displayValue.value === '' || displayValue.value === '0') {
        return '0';
    }
    const num = parseFloat(displayValue.value);
    if (isNaN(num)) {
        return displayValue.value;
    }
    if (displayValue.value.includes('.') && displayValue.value.endsWith('.')) {
        return num.toLocaleString() + '.';
    }
    if (displayValue.value.includes('.')) {
        const parts = displayValue.value.split('.');
        return parseFloat(parts[0]).toLocaleString() + '.' + parts[1];
    }
    return num.toLocaleString();
});

const formattedModelValue = computed(() => {
    const val = model.value;
    if (val === '' || val === null || val === undefined) {
        return '';
    }
    const num = parseFloat(val);
    if (isNaN(num)) {
        return '';
    }
    return num.toLocaleString();
});

const updateTeleportTarget = () => {
    teleportTarget.value = inputRef.value?.closest('dialog') ?? 'body';
};

const updateCalculatorPosition = () => {
    if (!showCalculator.value || !inputRef.value) {
        return;
    }

    updateTeleportTarget();

    if (window.innerWidth < 640) {
        calculatorStyle.value = {
            top: '16px',
            left: '16px',
            right: '16px',
            maxHeight: 'calc(100vh - 32px)',
        };

        return;
    }

    const viewportPadding = 16;
    const offset = 8;
    const inputRect = inputRef.value.getBoundingClientRect();
    const popupWidth = Math.min(inputRect.width, window.innerWidth - viewportPadding * 2);
    const popupHeight = calculatorRef.value?.offsetHeight ?? 420;
    const spaceAbove = inputRect.top - viewportPadding - offset;
    const spaceBelow = window.innerHeight - inputRect.bottom - viewportPadding - offset;
    const shouldOpenAbove = spaceAbove >= popupHeight || spaceAbove > spaceBelow;
    const availableHeight = Math.max(220, shouldOpenAbove ? spaceAbove : spaceBelow);
    const top = shouldOpenAbove
        ? Math.max(viewportPadding, inputRect.top - Math.min(popupHeight, availableHeight) - offset)
        : inputRect.bottom + offset;
    const left = Math.min(
        Math.max(viewportPadding, inputRect.left),
        window.innerWidth - popupWidth - viewportPadding,
    );

    calculatorStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        width: `${popupWidth}px`,
        maxHeight: `${availableHeight}px`,
    };
};

const openCalculator = async () => {
    updateTeleportTarget();
    showCalculator.value = true;
    displayValue.value = model.value ? String(model.value) : '';
    currentOperator.value = null;
    previousValue.value = null;
    waitingForOperand.value = false;

    await nextTick();
    updateCalculatorPosition();
};

const closeCalculator = () => {
    showCalculator.value = false;
};

const handleClickOutside = (event) => {
    if (
        calculatorRef.value &&
        !calculatorRef.value.contains(event.target) &&
        inputRef.value &&
        !inputRef.value.contains(event.target)
    ) {
        closeCalculator();
    }
};

const handleViewportChange = () => {
    if (showCalculator.value) {
        updateCalculatorPosition();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('scroll', handleViewportChange, true);
    window.addEventListener('resize', handleViewportChange);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('scroll', handleViewportChange, true);
    window.removeEventListener('resize', handleViewportChange);
});

const inputDigit = (digit) => {
    if (waitingForOperand.value) {
        displayValue.value = digit;
        waitingForOperand.value = false;
    } else {
        if (displayValue.value === '0' && digit !== '.') {
            displayValue.value = digit;
        } else {
            displayValue.value += digit;
        }
    }
};

const inputDecimal = () => {
    if (waitingForOperand.value) {
        displayValue.value = '0.';
        waitingForOperand.value = false;
        return;
    }
    if (!displayValue.value.includes('.')) {
        displayValue.value += '.';
    }
};

const clearAll = () => {
    displayValue.value = '';
    currentOperator.value = null;
    previousValue.value = null;
    waitingForOperand.value = false;
    model.value = '';
};

const backspace = () => {
    if (displayValue.value.length > 0) {
        displayValue.value = displayValue.value.slice(0, -1);
    }
};

const performOperation = (nextOperator) => {
    const inputValue = parseFloat(displayValue.value) || 0;

    if (previousValue.value === null) {
        previousValue.value = inputValue;
    } else if (currentOperator.value) {
        const result = calculate(previousValue.value, inputValue, currentOperator.value);
        displayValue.value = String(result);
        previousValue.value = result;
    }

    waitingForOperand.value = true;
    currentOperator.value = nextOperator;
};

const calculate = (left, right, operator) => {
    switch (operator) {
        case '+':
            return left + right;
        case '-':
            return left - right;
        case '×':
            return left * right;
        case '÷':
            return right !== 0 ? left / right : 0;
        default:
            return right;
    }
};

const executeEquals = () => {
    if (currentOperator.value && previousValue.value !== null) {
        const inputValue = parseFloat(displayValue.value) || 0;
        const result = calculate(previousValue.value, inputValue, currentOperator.value);
        displayValue.value = String(result);
        previousValue.value = null;
        currentOperator.value = null;
        waitingForOperand.value = false;
    }

    const finalValue = parseFloat(displayValue.value);
    if (!isNaN(finalValue) && finalValue > 0) {
        model.value = Math.round(finalValue);
    } else if (displayValue.value === '' || displayValue.value === '0') {
        model.value = '';
    }

    closeCalculator();
};

const operatorButtons = [
    { label: '+', value: '+' },
    { label: '-', value: '-' },
    { label: '×', value: '×' },
    { label: '÷', value: '÷' },
];

const numberButtons = [
    ['7', '8', '9'],
    ['4', '5', '6'],
    ['1', '2', '3'],
];
</script>

<template>
    <div class="relative">
        <div
            ref="inputRef"
            @click="openCalculator"
            class="mt-1 block w-full cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 touch-manipulation"
        >
            <span v-if="formattedModelValue" class="text-gray-900">
                {{ formattedModelValue }}
            </span>
            <span v-else class="text-gray-400">
                {{ placeholder || '金額を入力' }}
            </span>
        </div>

        <Teleport :to="teleportTarget">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-1"
            >
                <div
                    v-if="showCalculator"
                    ref="calculatorRef"
                    class="fixed z-[70] overflow-y-auto rounded-lg border border-gray-200 bg-white p-3 shadow-lg"
                    :style="calculatorStyle"
                >
                <div
                    class="mb-3 rounded-md bg-gray-100 px-4 py-3 text-right text-2xl font-semibold text-gray-800"
                >
                    <span v-if="currentOperator" class="mr-2 text-sm text-gray-500">
                        {{ previousValue?.toLocaleString() }} {{ currentOperator }}
                    </span>
                    {{ formattedDisplayValue }}
                </div>

                <div class="mb-2 grid grid-cols-4 gap-2">
                    <button
                        v-for="op in operatorButtons"
                        :key="op.value"
                        @click="performOperation(op.value)"
                        type="button"
                        class="h-12 rounded-md bg-gray-200 text-lg font-medium text-gray-700 transition hover:bg-gray-300 active:bg-gray-400 touch-manipulation"
                        :class="{ 'bg-indigo-100 text-indigo-700': currentOperator === op.value }"
                    >
                        {{ op.label }}
                    </button>
                </div>

                <div class="grid grid-cols-4 gap-2">
                    <template v-for="(row, rowIndex) in numberButtons" :key="rowIndex">
                        <button
                            v-for="num in row"
                            :key="num"
                            @click="inputDigit(num)"
                            type="button"
                            class="h-14 rounded-md bg-gray-100 text-xl font-medium text-gray-800 transition hover:bg-gray-200 active:bg-gray-300 touch-manipulation"
                        >
                            {{ num }}
                        </button>
                        <button
                            v-if="rowIndex === 0"
                            @click="clearAll"
                            type="button"
                            class="h-14 rounded-md bg-amber-500 text-lg font-medium text-white transition hover:bg-amber-600 active:bg-amber-700 touch-manipulation"
                        >
                            AC
                        </button>
                        <button
                            v-else-if="rowIndex === 1"
                            @click="backspace"
                            type="button"
                            class="h-14 rounded-md bg-amber-500 text-lg font-medium text-white transition hover:bg-amber-600 active:bg-amber-700 touch-manipulation"
                        >
                            ⌫
                        </button>
                        <template v-else-if="rowIndex === 2">
                            <button
                                @click="executeEquals"
                                type="button"
                                class="row-span-2 h-full rounded-md bg-amber-500 text-2xl font-medium text-white transition hover:bg-amber-600 active:bg-amber-700 touch-manipulation"
                            >
                                =
                            </button>
                        </template>
                    </template>

                    <button
                        @click="inputDigit('0')"
                        type="button"
                        class="col-span-2 h-14 rounded-md bg-gray-100 text-xl font-medium text-gray-800 transition hover:bg-gray-200 active:bg-gray-300 touch-manipulation"
                    >
                        0
                    </button>
                    <button
                        @click="inputDecimal"
                        type="button"
                        class="h-14 rounded-md bg-gray-100 text-xl font-medium text-gray-800 transition hover:bg-gray-200 active:bg-gray-300 touch-manipulation"
                    >
                        .
                    </button>
                </div>
                </div>
            </Transition>
        </Teleport>

        <input
            type="hidden"
            :id="id"
            :value="model"
        />
    </div>
</template>
