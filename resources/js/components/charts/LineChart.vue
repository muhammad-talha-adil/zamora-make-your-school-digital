<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    CategoryScale,
    Chart,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
    type ChartConfiguration,
} from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);

/**
 * A thin Chart.js wrapper for the small day-by-day trend lines used across
 * the Fee, Finance, and Attendance dashboards. Chart.js renders to canvas, so
 * it cannot inherit CSS custom properties the way the rest of the app's dark
 * mode does — colors are read from the live `documentElement` custom
 * properties (the same tokens `useAppearance`'s `updateTheme` writes) and
 * re-read whenever the class/style attributes change, so a theme switch or a
 * saved palette both take effect immediately.
 */
interface Point {
    label: string;
    value: number;
}

const props = withDefaults(
    defineProps<{
        points: Point[];
        formatValue?: (value: number) => string;
        /** CSS custom property name (on :root) driving the line/fill color. */
        colorVar?: string;
        height?: number;
    }>(),
    {
        formatValue: (value: number) => String(value),
        colorVar: '--primary',
        height: 220,
    },
);

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart<'line'> | null = null;
let observer: MutationObserver | null = null;

function readToken(name: string, fallback: string): string {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value || fallback;
}

function withAlpha(color: string, alpha: number): string {
    const hex = color.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);

    if (!hex) {
        return color;
    }

    let value = hex[1];

    if (value.length === 3) {
        value = value
            .split('')
            .map((char) => char + char)
            .join('');
    }

    const r = parseInt(value.slice(0, 2), 16);
    const g = parseInt(value.slice(2, 4), 16);
    const b = parseInt(value.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function themeColors() {
    return {
        line: readToken(props.colorVar, '#0f172a'),
        grid: readToken('--border', '#e2e8f0'),
        text: readToken('--muted-foreground', '#64748b'),
    };
}

function buildConfig(): ChartConfiguration<'line'> {
    const { line, grid, text } = themeColors();

    return {
        type: 'line',
        data: {
            labels: props.points.map((point) => point.label),
            datasets: [
                {
                    data: props.points.map((point) => point.value),
                    borderColor: line,
                    backgroundColor: withAlpha(line, 0.15),
                    pointBackgroundColor: line,
                    pointBorderColor: line,
                    pointRadius: 3,
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => props.formatValue(Number(context.parsed.y)),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: text, font: { size: 10 } },
                },
                y: {
                    grid: { color: grid },
                    ticks: {
                        color: text,
                        font: { size: 10 },
                        callback: (value) => props.formatValue(Number(value)),
                    },
                },
            },
        },
    };
}

function renderChart(): void {
    if (!canvasRef.value) {
        return;
    }

    chart?.destroy();
    chart = new Chart(canvasRef.value, buildConfig());
}

onMounted(() => {
    renderChart();

    observer = new MutationObserver(() => renderChart());
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'style'] });
});

watch(
    () => [props.points, props.colorVar],
    () => renderChart(),
    { deep: true },
);

onBeforeUnmount(() => {
    observer?.disconnect();
    chart?.destroy();
});
</script>

<template>
    <div class="w-full" :style="{ height: `${height}px` }">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>
