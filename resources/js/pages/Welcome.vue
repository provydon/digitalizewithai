<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useIntersectionObserver } from '@vueuse/core';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const page = usePage();
const branding = computed(() => (page.props.branding as { name: string }) ?? { name: 'Digitalize with AI' });
const pageTitle = computed(() => branding.value.name);

const featuresRef = ref<HTMLElement | null>(null);
const isRevealed = ref(false);
useIntersectionObserver(
    featuresRef,
    ([{ isIntersecting }]) => {
        if (isIntersecting) isRevealed.value = true;
    },
    { threshold: 0.08, rootMargin: '0px 0px -40px 0px' },
);
</script>

<template>
    <Head :title="pageTitle">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col bg-background text-foreground pb-72"
    >
        <header
            class="sticky top-0 z-10 w-full border-b border-border bg-background/95 px-4 py-3 backdrop-blur-sm sm:px-6 sm:py-4 lg:px-12"
        >
            <nav class="mx-auto flex max-w-5xl items-center justify-between gap-3 min-w-0">
                <Link
                    :href="$page.props.auth.user ? dashboard() : '/'"
                    class="flex min-w-0 shrink items-center gap-2 sm:gap-3 font-medium text-foreground no-underline"
                >
                    <AppLogoIcon class="h-6 w-6 shrink-0 sm:h-8 text-foreground" />
                    <span class="text-lg font-semibold text-foreground truncate">{{ branding.name }}</span>
                </Link>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-xl border border-border px-3 py-2 text-xs font-medium text-foreground transition-colors hover:bg-muted sm:px-4 sm:py-2.5 sm:text-sm"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-xl px-3 py-2 text-xs font-medium text-foreground transition-colors hover:bg-muted sm:px-4 sm:py-2.5 sm:text-sm"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-xl bg-primary px-3 py-2 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90 sm:px-4 sm:py-2.5 sm:text-sm"
                        >
                            Get started
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <main class="flex-1 px-6 pb-20 lg:px-12">
            <!-- Hero -->
            <section class="mx-auto max-w-5xl pt-16 text-center lg:pt-24">
                <h1 class="mb-4 text-4xl font-bold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                    {{ branding.name }}
                </h1>
                <p class="mx-auto max-w-2xl text-lg leading-relaxed text-foreground/90 lg:text-xl">
                    Physical records, logs, and books → digital. Search it, chat with AI, get insights. In one place.
                </p>
                <p class="mt-3 text-base font-medium text-primary">
                    Amazon Nova enabled
                </p>
                <div v-if="!$page.props.auth.user" class="mt-10 flex flex-wrap justify-center gap-4">
                    <Link
                        :href="register()"
                        class="inline-flex items-center rounded-xl bg-primary px-6 py-3 text-base font-medium text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:bg-primary/90 hover:shadow-primary/30"
                    >
                        Get started free
                    </Link>
                    <Link
                        :href="login()"
                        class="inline-flex items-center rounded-xl border border-border bg-card px-6 py-3 text-base font-medium text-foreground transition-colors hover:bg-muted"
                    >
                        Log in
                    </Link>
                </div>
                <div v-else class="mt-10">
                    <Link
                        :href="dashboard()"
                        class="inline-flex items-center rounded-xl bg-primary px-6 py-3 text-base font-medium text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:bg-primary/90"
                    >
                        Go to Dashboard
                    </Link>
                </div>
            </section>

            <!-- Why / About -->
            <section class="mx-auto max-w-3xl pt-16 lg:pt-20">
                <h2 class="mb-6 text-center text-xl font-semibold text-foreground lg:text-2xl">
                    Why {{ branding.name }}?
                </h2>
                <p class="mb-4 text-center text-base leading-relaxed text-foreground/90 sm:text-lg">
                    Imagine handwritten logs, sale records, or a book you don't have time to read or search.
                </p>
                <ul class="mx-auto max-w-xl list-disc space-y-2 pl-5 text-left text-base leading-relaxed text-foreground/90 sm:text-lg [&>li]:pl-1">
                    <li>Now you can upload <strong class="text-foreground">photos or a video</strong> of that physical data—we turn it into digital text and tables.</li>
                    <li>You can <strong class="text-foreground">search it instantly</strong>, <strong class="text-foreground">chat with AI</strong> about it, and generate graphs and insights.</li>
                    <li>You can turn books into audiobooks and ask the AI anything.</li>
                    <li>You get it all in one place: physical to digital, searchable, and AI-powered.</li>
                </ul>
            </section>

            <!-- Features -->
            <section class="mx-auto max-w-5xl pt-20 lg:pt-28">
                <h2 class="mb-10 text-center text-2xl font-semibold leading-snug text-foreground lg:text-3xl">
                    From <strong>physical data</strong> to digital—searchable, askable, processable
                </h2>
                <div
                    ref="featuresRef"
                    class="features-reveal grid gap-4 sm:gap-6 lg:grid-cols-2 lg:gap-8"
                    :class="{ 'reveal-done': isRevealed }"
                >
                    <!-- 1. Upload & digitalize (lead: how you get data in) -->
                    <div class="feature-card flex gap-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-2 font-semibold text-foreground">
                                Upload & digitalize
                            </h3>
                            <p class="text-sm leading-7 text-foreground/90 sm:text-base">
                                Upload <strong>photos or a video</strong> of handwritten tables, logs, sale records, or a physical book. We process it and turn it into digital text and tables—handwritten or printed, it doesn’t matter. Your physical data becomes digital in one place.
                            </p>
                        </div>
                    </div>
                    <!-- 2. Chat with your data -->
                    <div class="feature-card flex gap-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-2 font-semibold text-foreground">
                                Chat with AI about your data
                            </h3>
                            <p class="text-sm leading-7 text-foreground/90 sm:text-base">
                                An <strong>AI chat</strong> that knows your content. Ask questions, get summaries, or dig into specific rows and paragraphs. Conversations stay tied to each doc or table—save them and return anytime.
                            </p>
                        </div>
                    </div>
                    <!-- 3. Instant search -->
                    <div class="feature-card flex gap-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-2 font-semibold text-foreground">
                                Search instantly through tables & records
                            </h3>
                            <p class="text-sm leading-7 text-foreground/90 sm:text-base">
                                You can’t Ctrl+F a physical ledger or a stack of handwritten logs. Once your data is digitalized, <strong>search through tables, records, and logs</strong> as if they’d always been digital—no more flipping pages or squinting at handwriting.
                            </p>
                        </div>
                    </div>
                    <!-- 4. Graphs & business insights -->
                    <div class="feature-card flex gap-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-2 font-semibold text-foreground">
                                Graphs & business insights with AI
                            </h3>
                            <p class="text-sm leading-7 text-foreground/90 sm:text-base">
                                Generate <strong>charts and business insights</strong> from your data with AI. Turn tables and logs into visualizations and reports without manual number-crunching—your physical records become actionable intelligence.
                            </p>
                        </div>
                    </div>
                    <!-- 5. Instant audiobooks -->
                    <div class="feature-card flex gap-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-2 font-semibold text-foreground">
                                Turn books into instant audiobooks
                            </h3>
                            <p class="text-sm leading-7 text-foreground/90 sm:text-base">
                                That physical book you don’t have time to read? <strong>Turn it into an instant audiobook</strong>. AI reads it for you, and you can ask questions about the book anytime—summaries, characters, or “what happened in chapter 3?”
                            </p>
                        </div>
                    </div>
                    <!-- 6. Export -->
                    <div class="feature-card flex gap-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-2 font-semibold text-foreground">
                                Export—or use in your tools
                            </h3>
                            <p class="text-sm leading-7 text-foreground/90 sm:text-base">
                                One-click export to <strong>Excel, PDF, JSON, or plain text</strong>. Your digitalized data is yours—use it in spreadsheets, docs, or your own systems. Physical in, digital out; you choose where it goes.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="mx-auto max-w-5xl pt-20 lg:pt-28">
                <div
                    class="rounded-2xl border border-border bg-accent px-8 py-12 text-center lg:py-16"
                >
                    <h2 class="mb-3 text-xl font-semibold text-foreground lg:text-2xl">
                        From physical to digital—searchable, askable, processable
                    </h2>
                    <p class="mx-auto max-w-md text-base leading-relaxed text-foreground/90">
                        Upload photos or video of your records, logs, or books. Get digital text and tables, then search, chat with AI, generate graphs and insights, or turn books into audiobooks. Try it free.
                    </p>
                    <div v-if="!$page.props.auth.user" class="mt-6">
                        <Link
                            :href="register()"
                            class="inline-flex rounded-xl bg-primary px-6 py-3 text-base font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            Create free account
                        </Link>
                    </div>
                    <div v-else class="mt-6">
                        <Link
                            :href="dashboard()"
                            class="inline-flex rounded-xl bg-primary px-6 py-3 text-base font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            Open Dashboard
                        </Link>
                    </div>
                </div>
            </section>
            </main>
    </div>
</template>
