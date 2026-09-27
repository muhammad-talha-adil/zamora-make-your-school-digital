<script setup lang="ts">
import { computed } from 'vue';

type IllustrationType = 'login' | 'register' | 'forgot' | 'reset' | 'verify' | 'confirm' | '2fa';

const props = defineProps<{
    type: IllustrationType;
}>();

// Shared gradient/def block reused by every scene so illustrations feel like
// one cohesive family rather than one-off wireframes.
const defs = `
    <defs>
        <linearGradient id="authGradPrimary" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="var(--primary)" stop-opacity="0.9" />
            <stop offset="100%" stop-color="var(--primary)" stop-opacity="0.55" />
        </linearGradient>
        <radialGradient id="authGlow" cx="50%" cy="50%" r="50%">
            <stop offset="0%" stop-color="var(--primary)" stop-opacity="0.18" />
            <stop offset="100%" stop-color="var(--primary)" stop-opacity="0" />
        </radialGradient>
    </defs>
`;

const illustrations: Record<IllustrationType, () => string> = {
    login: () => `
        <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${defs}
            <circle cx="160" cy="160" r="150" fill="url(#authGlow)" />
            <!-- Dashboard card -->
            <rect x="40" y="60" width="240" height="200" rx="20" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <rect x="40" y="60" width="240" height="40" rx="20" fill="url(#authGradPrimary)" />
            <rect x="40" y="80" width="240" height="20" fill="url(#authGradPrimary)" />
            <circle cx="60" cy="80" r="5" fill="white" fill-opacity="0.85" />
            <circle cx="76" cy="80" r="5" fill="white" fill-opacity="0.6" />
            <circle cx="92" cy="80" r="5" fill="white" fill-opacity="0.4" />
            <!-- Content blocks -->
            <rect x="58" y="118" width="96" height="60" rx="10" fill="var(--primary)" fill-opacity="0.12" />
            <rect x="166" y="118" width="96" height="60" rx="10" fill="var(--primary)" fill-opacity="0.08" />
            <rect x="58" y="192" width="204" height="14" rx="7" fill="var(--muted-foreground)" fill-opacity="0.25" />
            <rect x="58" y="214" width="140" height="14" rx="7" fill="var(--muted-foreground)" fill-opacity="0.18" />
            <path d="M76 158 L92 170 L118 134" stroke="var(--primary)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none" />
            <circle cx="214" cy="148" r="18" stroke="var(--primary)" stroke-width="4" fill="none" opacity="0.85" />
            <path d="M214 140 L214 148 L220 154" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" />
            <!-- Floating lock badge -->
            <circle cx="252" cy="238" r="26" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <rect x="242" y="234" width="20" height="16" rx="4" fill="url(#authGradPrimary)" />
            <path d="M246 234 v-6 a6 6 0 0 1 12 0 v6" stroke="var(--primary)" stroke-width="3" fill="none" />
        </svg>
    `,
    register: () => `
        <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${defs}
            <circle cx="160" cy="160" r="150" fill="url(#authGlow)" />
            <rect x="40" y="70" width="240" height="190" rx="20" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <!-- Growth bars -->
            <rect x="70" y="180" width="26" height="50" rx="6" fill="var(--primary)" fill-opacity="0.18" />
            <rect x="106" y="150" width="26" height="80" rx="6" fill="var(--primary)" fill-opacity="0.3" />
            <rect x="142" y="120" width="26" height="110" rx="6" fill="var(--primary)" fill-opacity="0.45" />
            <rect x="178" y="95" width="26" height="135" rx="6" fill="url(#authGradPrimary)" />
            <!-- Growth line -->
            <path d="M80 200 L118 165 L154 185 L190 130 L226 105" stroke="var(--primary)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
            <circle cx="226" cy="105" r="7" fill="var(--primary)" />
            <path d="M214 95 L226 105 L238 95" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" />
            <!-- New account badge -->
            <circle cx="238" cy="90" r="24" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <path d="M238 79 v22 M227 90 h22" stroke="var(--primary)" stroke-width="3.5" stroke-linecap="round" />
        </svg>
    `,
    forgot: () => `
        <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${defs}
            <circle cx="160" cy="160" r="150" fill="url(#authGlow)" />
            <!-- Envelope -->
            <rect x="60" y="130" width="200" height="130" rx="16" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <path d="M60 140 L160 210 L260 140" stroke="var(--border)" stroke-width="1.5" fill="none" stroke-linejoin="round" />
            <path d="M60 140 L160 210 L260 140 L260 146 L160 216 L60 146 Z" fill="var(--primary)" fill-opacity="0.08" />
            <!-- Key -->
            <g transform="translate(178, 46)">
                <circle cx="0" cy="0" r="22" fill="url(#authGradPrimary)" />
                <circle cx="0" cy="0" r="9" fill="var(--card)" />
                <rect x="-3.5" y="16" width="7" height="46" rx="3.5" fill="url(#authGradPrimary)" />
                <rect x="-3.5" y="46" width="18" height="7" rx="3.5" fill="url(#authGradPrimary)" />
                <rect x="-3.5" y="58" width="14" height="7" rx="3.5" fill="url(#authGradPrimary)" />
            </g>
            <!-- Motion sparkle -->
            <path d="M240 60 l4 10 10 4 -10 4 -4 10 -4 -10 -10 -4 10 -4 Z" fill="var(--primary)" opacity="0.55" />
        </svg>
    `,
    reset: () => `
        <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${defs}
            <circle cx="160" cy="160" r="150" fill="url(#authGlow)" />
            <!-- Shield -->
            <path d="M160 46 L262 88 L262 178 C262 232 160 268 160 268 C160 268 58 232 58 178 L58 88 Z" fill="url(#authGradPrimary)" fill-opacity="0.14" stroke="var(--primary)" stroke-width="2.5" />
            <circle cx="160" cy="150" r="42" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <path d="M140 150 L154 164 L182 128" stroke="var(--primary)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
            <!-- Sparkles -->
            <g fill="var(--primary)" opacity="0.55">
                <path d="M244 78 l3 8 8 3 -8 3 -3 8 -3 -8 -8 -3 8 -3 Z" />
                <path d="M70 100 l2 6 6 2 -6 2 -2 6 -2 -6 -6 -2 6 -2 Z" />
                <path d="M232 220 l2 6 6 2 -6 2 -2 6 -2 -6 -6 -2 6 -2 Z" />
            </g>
        </svg>
    `,
    verify: () => `
        <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${defs}
            <circle cx="160" cy="160" r="150" fill="url(#authGlow)" />
            <rect x="50" y="90" width="220" height="150" rx="18" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <path d="M50 100 L160 180 L270 100" stroke="var(--border)" stroke-width="1.5" fill="none" stroke-linejoin="round" />
            <path d="M50 100 L160 180 L270 100 L270 108 L160 188 L50 108 Z" fill="var(--primary)" fill-opacity="0.08" />
            <rect x="80" y="210" width="160" height="10" rx="5" fill="var(--muted-foreground)" fill-opacity="0.2" />
            <rect x="80" y="228" width="110" height="10" rx="5" fill="var(--muted-foreground)" fill-opacity="0.15" />
            <!-- Checkmark badge -->
            <circle cx="222" cy="118" r="28" fill="url(#authGradPrimary)" />
            <path d="M210 118 L219 128 L236 106" stroke="var(--primary-foreground)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none" />
        </svg>
    `,
    confirm: () => `
        <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${defs}
            <circle cx="160" cy="160" r="150" fill="url(#authGlow)" />
            <rect x="96" y="130" width="128" height="110" rx="18" fill="url(#authGradPrimary)" />
            <path d="M118 130 v-24 a42 42 0 0 1 84 0 v24" stroke="var(--primary)" stroke-width="7" fill="none" />
            <circle cx="160" cy="176" r="14" fill="var(--card)" />
            <rect x="155" y="188" width="10" height="24" rx="5" fill="var(--card)" />
            <!-- Confirm check -->
            <circle cx="230" cy="222" r="26" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <path d="M219 222 L227 230 L242 212" stroke="var(--primary)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none" />
        </svg>
    `,
    '2fa': () => `
        <svg viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg">
            ${defs}
            <circle cx="160" cy="160" r="150" fill="url(#authGlow)" />
            <!-- Phone -->
            <rect x="108" y="56" width="104" height="204" rx="22" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <rect x="122" y="80" width="76" height="126" rx="10" fill="var(--primary)" fill-opacity="0.08" />
            <rect x="132" y="96" width="56" height="26" rx="6" fill="url(#authGradPrimary)" />
            <text x="160" y="114" fill="var(--primary-foreground)" font-family="monospace" font-size="13" font-weight="700" text-anchor="middle">123 456</text>
            <g fill="var(--primary)" fill-opacity="0.7">
                <circle cx="140" cy="150" r="6" />
                <circle cx="160" cy="150" r="6" />
                <circle cx="180" cy="150" r="6" />
                <circle cx="140" cy="176" r="6" />
                <circle cx="160" cy="176" r="6" />
                <circle cx="180" cy="176" r="6" />
            </g>
            <rect x="150" y="238" width="20" height="6" rx="3" fill="var(--border)" />
            <!-- Shield badge -->
            <circle cx="222" cy="80" r="26" fill="var(--card)" stroke="var(--border)" stroke-width="1.5" />
            <path d="M222 66 L234 71 L234 82 C234 90 222 94 222 94 C222 94 210 90 210 82 L210 71 Z" fill="url(#authGradPrimary)" />
        </svg>
    `,
};

const illustrationHtml = computed(() => illustrations[props.type]());
</script>

<template>
    <div class="relative w-full h-full" v-html="illustrationHtml" />
</template>
