<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Digitalize with AI — Amazon Nova">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col bg-white text-[#161d26] dark:bg-[#0f0f11] dark:text-[#e5e5e7]"
    >
        <header
            class="sticky top-0 z-10 w-full border-b border-[#e5e5e7] bg-white/95 px-6 py-4 backdrop-blur-sm dark:border-[#2a2a2e] dark:bg-[#0f0f11]/95 lg:px-12"
        >
            <nav class="mx-auto flex max-w-5xl items-center justify-between">
                <Link
                    :href="$page.props.auth.user ? dashboard() : '/'"
                    class="flex items-center gap-3"
                >
                    <!-- Amazon Nova logo -->
                    <svg
                        role="img"
                        class="h-8 w-auto dark:[--logo-text:#e5e5e7]"
                        viewBox="0 0 179 32"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-labelledby="logo-title"
                    >
                        <title id="logo-title">Amazon Nova</title>
                        <path
                            d="M41.1172 23.625C40.8609 23.625 40.7328 23.5264 40.7328 23.3293C40.7328 23.2534 40.7479 23.17 40.7781 23.079C40.8081 22.988 40.8383 22.897 40.8685 22.806L46.1587 8.4735C46.234 8.246 46.317 8.08675 46.4074 7.99575C46.4979 7.90475 46.6712 7.85925 46.9274 7.85925H48.3291C48.5852 7.85925 48.7587 7.90475 48.8491 7.99575C48.9395 8.08675 49.0224 8.246 49.0978 8.4735L54.388 22.806C54.4181 22.897 54.4483 22.988 54.4785 23.079C54.5085 23.17 54.5237 23.2534 54.5237 23.3293C54.5237 23.5264 54.3955 23.625 54.1393 23.625H52.8507C52.5944 23.625 52.4211 23.5795 52.3307 23.4885C52.2403 23.3975 52.1573 23.2383 52.082 23.0108L50.8386 19.439H44.3501L43.1519 23.0108C43.0765 23.2383 42.9936 23.3975 42.9032 23.4885C42.8128 23.5795 42.6394 23.625 42.3832 23.625H41.1172ZM44.9605 17.6873H50.2508L47.583 9.95225L44.9605 17.6873Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M57.9963 23.625H56.6624C56.3609 23.625 56.2102 23.4734 56.2102 23.17V12.5458C56.2102 12.2424 56.3609 12.0908 56.6624 12.0908H57.6571C57.9586 12.0908 58.1319 12.2348 58.1771 12.523L58.3128 13.251C59.0362 12.7505 59.7446 12.3789 60.4379 12.1363C61.1312 11.8784 61.832 11.7495 62.5404 11.7495C63.9421 11.7495 64.8917 12.2879 65.3891 13.3648C66.8812 12.2879 68.3733 11.7495 69.8654 11.7495C70.9054 11.7495 71.7042 12.0453 72.2619 12.6368C72.8195 13.2283 73.0983 14.07 73.0983 15.162V23.17C73.0983 23.4734 72.9477 23.625 72.6462 23.625H71.3123C71.0108 23.625 70.8602 23.4734 70.8602 23.17V15.799C70.8602 15.0255 70.7169 14.4644 70.4306 14.1155C70.1443 13.7515 69.6921 13.5695 69.0741 13.5695C67.9739 13.5695 66.8661 13.9108 65.7508 14.5933C65.7658 14.6994 65.7734 14.8131 65.7734 14.9345C65.7734 15.0559 65.7734 15.1771 65.7734 15.2985V23.17C65.7734 23.4734 65.6227 23.625 65.3212 23.625H63.9874C63.6859 23.625 63.5352 23.4734 63.5352 23.17V15.799C63.5352 15.0255 63.392 14.4644 63.1056 14.1155C62.8193 13.7515 62.3672 13.5695 61.7492 13.5695C60.6037 13.5695 59.5034 13.9031 58.4484 14.5705V23.17C58.4484 23.4734 58.2977 23.625 57.9963 23.625Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M78.8538 23.9208C77.7838 23.9208 76.9247 23.6099 76.2765 22.988C75.6435 22.3661 75.327 21.5471 75.327 20.531C75.327 19.4239 75.7189 18.5441 76.5026 17.892C77.2864 17.2246 78.3414 16.891 79.6677 16.891C80.5118 16.891 81.4613 17.0199 82.5163 17.2778V15.7763C82.5163 14.9573 82.328 14.3809 81.9511 14.0473C81.5894 13.7136 80.9639 13.5468 80.0746 13.5468C79.5924 13.5468 79.0799 13.5846 78.5373 13.6605C78.0099 13.7364 77.5049 13.8501 77.0226 14.0018C76.7965 14.0624 76.6382 14.1079 76.5478 14.1383C76.4725 14.1534 76.4121 14.161 76.3669 14.161C76.1861 14.161 76.0956 14.0245 76.0956 13.7515V13.1373C76.0956 12.9249 76.1258 12.7809 76.1861 12.705C76.2464 12.614 76.3669 12.5306 76.5478 12.4548C77.0301 12.2424 77.6255 12.0756 78.3338 11.9543C79.0573 11.8178 79.7657 11.7495 80.459 11.7495C81.8908 11.7495 82.9458 12.0453 83.6241 12.6368C84.3023 13.2283 84.6414 14.1459 84.6414 15.3895V23.17C84.6414 23.4734 84.4907 23.625 84.1893 23.625H83.2171C82.9308 23.625 82.765 23.4809 82.7197 23.1928L82.6067 22.442C82.0641 22.9121 81.4613 23.2761 80.7981 23.534C80.15 23.7919 79.502 23.9208 78.8538 23.9208ZM79.3964 22.2145C80.4515 22.2145 81.4915 21.8278 82.5163 21.0543V18.7338C82.1395 18.6428 81.7402 18.5745 81.3181 18.529C80.9111 18.4684 80.5118 18.438 80.1198 18.438C78.4168 18.438 77.5651 19.0978 77.5651 20.4173C77.5651 20.9784 77.7234 21.4183 78.0399 21.7368C78.3564 22.0553 78.8086 22.2145 79.3964 22.2145Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M95.4558 23.6249H87.2265C86.9251 23.6249 86.7744 23.4733 86.7744 23.1699V22.3964C86.7744 22.1538 86.797 21.9793 86.8422 21.8732C86.9025 21.7671 87.008 21.6229 87.1587 21.4409L93.3533 13.8652H87.4526C87.1511 13.8652 87.0005 13.7136 87.0005 13.4102V12.5457C87.0005 12.2424 87.1511 12.0907 87.4526 12.0907H95.388C95.6895 12.0907 95.8402 12.2424 95.8402 12.5457V13.2964C95.8402 13.5088 95.8176 13.6756 95.7723 13.7969C95.7271 13.9031 95.6291 14.0472 95.4784 14.2292L89.1708 21.8504H95.4558C95.7573 21.8504 95.908 21.9946 95.908 22.2827V23.1699C95.908 23.4733 95.7573 23.6249 95.4558 23.6249Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M102.709 23.9663C101.021 23.9663 99.687 23.4354 98.7072 22.3738C97.7276 21.2969 97.2377 19.7879 97.2377 17.8465C97.2377 15.9051 97.7276 14.4036 98.7072 13.342C99.687 12.2803 101.021 11.7495 102.709 11.7495C104.412 11.7495 105.746 12.2803 106.71 13.342C107.69 14.4036 108.18 15.9051 108.18 17.8465C108.18 19.7879 107.69 21.2969 106.71 22.3738C105.746 23.4354 104.412 23.9663 102.709 23.9663ZM102.709 22.1463C103.749 22.1463 104.54 21.7974 105.083 21.0998C105.625 20.4021 105.897 19.3176 105.897 17.8465C105.897 16.3905 105.625 15.3136 105.083 14.616C104.54 13.9184 103.749 13.5695 102.709 13.5695C101.669 13.5695 100.878 13.9184 100.335 14.616C99.7924 15.3136 99.5211 16.3905 99.5211 17.8465C99.5211 19.3176 99.7924 20.4021 100.335 21.0998C100.878 21.7974 101.669 22.1463 102.709 22.1463Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M112.387 23.625H111.053C110.752 23.625 110.601 23.4734 110.601 23.17V12.5458C110.601 12.2424 110.752 12.0908 111.053 12.0908H112.048C112.349 12.0908 112.522 12.2348 112.568 12.523L112.703 13.3648C114.135 12.2879 115.642 11.7495 117.225 11.7495C118.325 11.7495 119.162 12.0453 119.734 12.6368C120.307 13.2131 120.593 14.0549 120.593 15.162V23.17C120.593 23.4734 120.443 23.625 120.141 23.625H118.807C118.506 23.625 118.355 23.4734 118.355 23.17V15.9128C118.355 15.1089 118.19 14.5174 117.858 14.1383C117.541 13.7591 117.037 13.5695 116.343 13.5695C115.74 13.5695 115.145 13.6681 114.557 13.8653C113.969 14.0624 113.397 14.3506 112.839 14.7298V23.17C112.839 23.4734 112.688 23.625 112.387 23.625Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M131.029 23.625H129.809C129.507 23.625 129.357 23.4734 129.357 23.17V8.31425C129.357 8.01092 129.507 7.85925 129.809 7.85925H131.255C131.677 7.85925 131.964 7.99575 132.115 8.26875L139.575 20.4628V8.31425C139.575 8.01092 139.726 7.85925 140.027 7.85925H141.249C141.55 7.85925 141.701 8.01092 141.701 8.31425V23.17C141.701 23.4734 141.55 23.625 141.249 23.625H139.779C139.372 23.625 139.086 23.4885 138.919 23.2155L131.481 11.0898V23.17C131.481 23.4734 131.331 23.625 131.029 23.625Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M149.813 23.9663C148.125 23.9663 146.791 23.4354 145.812 22.3738C144.832 21.2969 144.343 19.7879 144.343 17.8465C144.343 15.9051 144.832 14.4036 145.812 13.342C146.791 12.2803 148.125 11.7495 149.813 11.7495C151.517 11.7495 152.851 12.2803 153.816 13.342C154.795 14.4036 155.285 15.9051 155.285 17.8465C155.285 19.7879 154.795 21.2969 153.816 22.3738C152.851 23.4354 151.517 23.9663 149.813 23.9663ZM149.813 22.1463C150.853 22.1463 151.644 21.7974 152.187 21.0998C152.73 20.4021 153.001 19.3176 153.001 17.8465C153.001 16.3905 152.73 15.3136 152.187 14.616C151.644 13.9184 150.853 13.5695 149.813 13.5695C148.774 13.5695 147.982 13.9184 147.44 14.616C146.897 15.3136 146.626 16.3905 146.626 17.8465C146.626 19.3176 146.897 20.4021 147.44 21.0998C147.982 21.7974 148.774 22.1463 149.813 22.1463Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M162.187 23.6249H161.034C160.778 23.6249 160.604 23.5794 160.515 23.4884C160.439 23.3974 160.356 23.2382 160.265 23.0107L156.264 12.9097C156.234 12.8187 156.196 12.7277 156.151 12.6367C156.121 12.5306 156.106 12.4471 156.106 12.3864C156.106 12.1893 156.219 12.0907 156.446 12.0907H157.778C158.036 12.0907 158.201 12.1362 158.277 12.2272C158.367 12.303 158.457 12.4623 158.547 12.7049L161.645 21.5774L164.742 12.7049C164.833 12.4623 164.915 12.303 164.991 12.2272C165.081 12.1362 165.254 12.0907 165.511 12.0907H166.754C166.981 12.0907 167.094 12.1893 167.094 12.3864C167.094 12.4471 167.071 12.5306 167.025 12.6367C166.995 12.7277 166.966 12.8187 166.935 12.9097L162.956 23.0107C162.865 23.2382 162.776 23.3974 162.685 23.4884C162.609 23.5794 162.444 23.6249 162.187 23.6249Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <path
                            d="M171.518 23.9208C170.448 23.9208 169.589 23.6099 168.941 22.988C168.307 22.3661 167.992 21.5471 167.992 20.531C167.992 19.4239 168.383 18.5441 169.167 17.892C169.951 17.2246 171.005 16.891 172.332 16.891C173.175 16.891 174.126 17.0199 175.18 17.2778V15.7763C175.18 14.9573 174.993 14.3809 174.615 14.0473C174.254 13.7136 173.628 13.5468 172.739 13.5468C172.256 13.5468 171.744 13.5846 171.202 13.6605C170.674 13.7364 170.169 13.8501 169.687 14.0018C169.461 14.0624 169.302 14.1079 169.212 14.1383C169.137 14.1534 169.076 14.161 169.031 14.161C168.85 14.161 168.759 14.0245 168.759 13.7515V13.1373C168.759 12.9249 168.79 12.7809 168.85 12.705C168.911 12.614 169.031 12.5306 169.212 12.4548C169.695 12.2424 170.29 12.0756 170.998 11.9543C171.722 11.8178 172.43 11.7495 173.123 11.7495C174.555 11.7495 175.61 12.0453 176.288 12.6368C176.967 13.2283 177.306 14.1459 177.306 15.3895V23.17C177.306 23.4734 177.155 23.625 176.853 23.625H175.881C175.595 23.625 175.429 23.4809 175.384 23.1928L175.271 22.442C174.728 22.9121 174.126 23.2761 173.462 23.534C172.814 23.7919 172.167 23.9208 171.518 23.9208ZM172.061 22.2145C173.116 22.2145 174.155 21.8278 175.18 21.0543V18.7338C174.804 18.6428 174.404 18.5745 173.983 18.529C173.575 18.4684 173.175 18.438 172.784 18.438C171.081 18.438 170.229 19.0978 170.229 20.4173C170.229 20.9784 170.388 21.4183 170.705 21.7368C171.02 22.0553 171.472 22.2145 172.061 22.2145Z"
                            fill="currentColor"
                            class="text-[#161d26] dark:text-[#e5e5e7]"
                        />
                        <mask id="nova-logo-mask0" maskUnits="userSpaceOnUse" x="0" y="0" width="32" height="32" style="mask-type: luminance;">
                            <path d="M31.8001 0H0V32H31.8001V0Z" fill="white" />
                        </mask>
                        <g mask="url(#nova-logo-mask0)">
                            <mask id="nova-logo-mask1" maskUnits="userSpaceOnUse" x="0" y="0" width="32" height="32" style="mask-type: alpha;">
                                <path d="M17.8652 23.2806L19.3981 24.823C19.4676 24.893 19.49 24.998 19.4527 25.0905L17.0553 31.2075C16.869 31.685 16.4094 32 15.9001 32C15.3908 32 14.9312 31.685 14.7448 31.2075L11.294 22.4032L5.7116 28.0203C5.61471 28.1178 5.4582 28.1178 5.3613 28.0203L3.95514 26.6054C3.85825 26.5079 3.85825 26.3504 3.95514 26.2529L10.8443 19.3209C11.0679 19.096 11.3636 18.941 11.6791 18.9235C12.2306 18.8935 12.7176 19.2209 12.9114 19.7134L15.9026 27.3429L17.4603 23.3656C17.5249 23.1981 17.741 23.1531 17.8677 23.2806H17.8652ZM26.0885 3.97955L20.5061 9.59664L17.0553 0.792286C16.8342 0.229828 16.2404 -0.105148 15.6268 0.0298417C15.2119 0.122335 14.8815 0.437312 14.7274 0.834782L12.3474 6.90684C12.3101 6.99934 12.3325 7.10432 12.4021 7.17432L13.9349 8.7167C14.0616 8.8442 14.2753 8.7992 14.3424 8.63171L15.9001 4.6545L18.8913 12.2839C19.0453 12.6764 19.3832 12.9639 19.7931 13.0489C19.8776 13.0664 19.962 13.0739 20.0465 13.0739C20.372 13.0739 20.69 12.9439 20.926 12.7089L27.8475 5.74442C27.9444 5.64692 27.9444 5.48944 27.8475 5.39195L26.4413 3.97705C26.3444 3.87956 26.1879 3.87956 26.091 3.97705L26.0885 3.97955ZM8.57858 17.566L4.62593 15.9986L12.2083 12.9889C12.6977 12.7939 13.0231 12.3039 12.9933 11.749C12.9759 11.4315 12.8219 11.134 12.5983 10.909L5.7116 3.97955C5.61471 3.88206 5.4582 3.88206 5.3613 3.97955L3.95514 5.39445C3.85825 5.49194 3.85825 5.64943 3.95514 5.74692L9.53756 11.364L0.78755 14.8363C0.313033 15.0238 0 15.4862 0 15.9986C0 16.5111 0.313033 16.9736 0.78755 17.1611L6.86684 19.5734C6.95876 19.6109 7.06311 19.5884 7.13267 19.5184L8.66554 17.976C8.79224 17.8485 8.74752 17.6335 8.58107 17.566H8.57858ZM31.0126 14.8363L24.9333 12.4239C24.8414 12.3864 24.737 12.4089 24.6675 12.4789L23.1346 14.0214C23.0079 14.1487 23.0526 14.3637 23.2191 14.4312L27.1717 15.9986L19.5894 19.0085C19.1 19.2035 18.7745 19.6934 18.8043 20.2484C18.8217 20.5659 18.9757 20.8634 19.1993 21.0882L26.0885 28.0203C26.1854 28.1178 26.3419 28.1178 26.4388 28.0203L27.845 26.6054C27.9419 26.5079 27.9419 26.3504 27.845 26.2529L22.2626 20.6359L31.0126 17.1636C31.4871 16.9761 31.8001 16.5136 31.8001 16.0011C31.8001 15.4888 31.4871 15.0262 31.0126 14.8387V14.8363Z"
                                    fill="white"
                                />
                            </mask>
                            <g mask="url(#nova-logo-mask1)">
                                <path
                                    d="M-2.91543 34.125L34.533 34.125L34.533 -2.1095L-2.91543 -2.1095L-2.91543 34.125Z"
                                    fill="url(#nova-logo-gradient)"
                                />
                            </g>
                        </g>
                        <defs>
                            <linearGradient id="nova-logo-gradient" x1="33.6628" y1="33.633" x2="-2.08592" y2="-1.90102" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#ff6200" />
                                <stop offset="0.398661" stop-color="#e433ff" />
                                <stop offset="0.96" stop-color="#7c3aed" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <span class="text-sm font-medium text-[#6b6b6e] dark:text-[#9b9b9e]">
                        Digitalize with AI
                    </span>
                </Link>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-xl border border-[#d1d1d6] px-4 py-2.5 text-sm font-medium text-[#161d26] transition-colors hover:bg-[#f5f5f7] dark:border-[#3a3a3e] dark:text-[#e5e5e7] dark:hover:bg-[#1c1c1e]"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-[#161d26] transition-colors hover:bg-[#f5f5f7] dark:text-[#e5e5e7] dark:hover:bg-[#1c1c1e]"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-xl bg-[#7c3aed] px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#6d28d9] dark:bg-[#8b5cf6] dark:hover:bg-[#7c3aed]"
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
                <p class="mb-3 text-sm font-medium uppercase tracking-wider text-[#7c3aed] dark:text-[#a78bfa]">
                    Powered by Amazon Nova
                </p>
                <h1 class="mb-4 text-4xl font-bold tracking-tight text-[#161d26] dark:text-[#e5e5e7] sm:text-5xl lg:text-6xl">
                    Digitalize with AI
                </h1>
                <p class="mx-auto max-w-2xl text-lg text-[#6b6b6e] dark:text-[#9b9b9e] lg:text-xl">
                    Turn Physical books, notes, documents, logs, records and tables into
                    editable and exportable digital content and get actionable insights with AI—using Amazon Nova.
                </p>
                <p class="mx-auto mt-3 max-w-xl text-sm text-[#6b6b6e] dark:text-[#9b9b9e]">
                    Upload an image or record a video. Nova’s multimodal AI extracts the content and gives you
                    clean text or structured tables you can view, edit, and build on.
                </p>
                <div v-if="!$page.props.auth.user" class="mt-10 flex flex-wrap justify-center gap-4">
                    <Link
                        :href="register()"
                        class="inline-flex items-center rounded-xl bg-[#7c3aed] px-6 py-3 text-base font-medium text-white shadow-lg shadow-[#7c3aed]/25 transition-all hover:bg-[#6d28d9] hover:shadow-[#7c3aed]/30 dark:bg-[#8b5cf6] dark:hover:bg-[#7c3aed]"
                    >
                        Get started free
                    </Link>
                    <Link
                        :href="login()"
                        class="inline-flex items-center rounded-xl border border-[#d1d1d6] bg-white px-6 py-3 text-base font-medium text-[#161d26] transition-colors hover:bg-[#f5f5f7] dark:border-[#3a3a3e] dark:bg-[#1c1c1e] dark:text-[#e5e5e7] dark:hover:bg-[#2a2a2e]"
                    >
                        Log in
                    </Link>
                </div>
                <div v-else class="mt-10">
                    <Link
                        :href="dashboard()"
                        class="inline-flex items-center rounded-xl bg-[#7c3aed] px-6 py-3 text-base font-medium text-white shadow-lg shadow-[#7c3aed]/25 transition-all hover:bg-[#6d28d9] dark:bg-[#8b5cf6] dark:hover:bg-[#7c3aed]"
                    >
                        Go to Dashboard
                    </Link>
                </div>
            </section>

            <!-- What it does -->
            <section class="mx-auto max-w-5xl pt-20 lg:pt-28">
                <h2 class="mb-10 text-center text-2xl font-semibold text-[#161d26] dark:text-[#e5e5e7] lg:text-3xl">
                    What you can do
                </h2>
                <div
                    class="grid gap-8 rounded-2xl border border-[#e5e5e7] bg-[#fafafa] p-8 dark:border-[#2a2a2e] dark:bg-[#1c1c1e] lg:grid-cols-2 lg:gap-12 lg:p-12"
                >
                    <div class="flex gap-4">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7c3aed]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/20 dark:text-[#a78bfa]"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-1 font-semibold text-[#161d26] dark:text-[#e5e5e7]">
                                Upload & extract
                            </h3>
                            <p class="text-sm leading-relaxed text-[#6b6b6e] dark:text-[#9b9b9e]">
                                Upload a photo or record a video from your device or camera. Amazon Nova detects whether the content is a document (prose, notes) or a table and returns structured text or rows you can use right away.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7c3aed]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/20 dark:text-[#a78bfa]"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-1 font-semibold text-[#161d26] dark:text-[#e5e5e7]">
                                Documents
                            </h3>
                            <p class="text-sm leading-relaxed text-[#6b6b6e] dark:text-[#9b9b9e]">
                                View extracted text as clean, readable content. Edit inline, copy to clipboard, and ask Nova questions about your document. Multi-page docs are supported.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7c3aed]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/20 dark:text-[#a78bfa]"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125M12 18.375v-1.5m0 1.5c0 .621.504 1.125 1.125 1.125M12 12.375v-1.5m0 1.5c0 .621.504 1.125 1.125 1.125m0 0h7.5m-7.5 0c.621 0 1.125.504 1.125 1.125M12 21.375v-1.5m0 1.5c0 .621.504 1.125 1.125 1.125m0 0h7.5m-7.5 0c.621 0 1.125.504 1.125 1.125m0 0v-1.5m0 1.5v-1.5m0 1.5V21m0 0h7.5m-7.5 0h7.5" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-1 font-semibold text-[#161d26] dark:text-[#e5e5e7]">
                                Tables
                            </h3>
                            <p class="text-sm leading-relaxed text-[#6b6b6e] dark:text-[#9b9b9e]">
                                See extracted data as a proper table with headers and rows. Edit cells, add rows manually, or add rows by uploading another photo or video—new data is appended. Search and paginate large datasets.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7c3aed]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/20 dark:text-[#a78bfa]"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-1 font-semibold text-[#161d26] dark:text-[#e5e5e7]">
                                Powered by Amazon Nova
                            </h3>
                            <p class="text-sm leading-relaxed text-[#6b6b6e] dark:text-[#9b9b9e]">
                                Extraction and understanding use Amazon Nova’s multimodal AI so you get accurate text and structured tables from handwritten or printed sources—including from video frames.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7c3aed]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/20 dark:text-[#a78bfa]"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-1 font-semibold text-[#161d26] dark:text-[#e5e5e7]">
                                Get insights with AI
                            </h3>
                            <p class="text-sm leading-relaxed text-[#6b6b6e] dark:text-[#9b9b9e]">
                                Ask Nova questions about your documents and tables. Get summaries, key points, trends, and actionable insights from your digitalized content.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7c3aed]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/20 dark:text-[#a78bfa]"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-1 font-semibold text-[#161d26] dark:text-[#e5e5e7]">
                                Generate charts
                            </h3>
                            <p class="text-sm leading-relaxed text-[#6b6b6e] dark:text-[#9b9b9e]">
                                Turn table data into visual charts with AI. Nova suggests the best chart types and helps you visualize trends, comparisons, and distributions at a glance.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7c3aed]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/20 dark:text-[#a78bfa]"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-1 font-semibold text-[#161d26] dark:text-[#e5e5e7]">
                                Search with AI
                            </h3>
                            <p class="text-sm leading-relaxed text-[#6b6b6e] dark:text-[#9b9b9e]">
                                Search across your documents and table rows. Use natural language or keywords—Nova helps you find exactly what you need in your digitalized content.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#7c3aed]/10 text-[#7c3aed] dark:bg-[#8b5cf6]/20 dark:text-[#a78bfa]"
                            aria-hidden
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="mb-1 font-semibold text-[#161d26] dark:text-[#e5e5e7]">
                                Export anywhere
                            </h3>
                            <p class="text-sm leading-relaxed text-[#6b6b6e] dark:text-[#9b9b9e]">
                                Export to Excel, docs, notes, or JSON. Turn physical table logs and records into clean JSON or spreadsheets—use your data in the tools you already use.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="mx-auto max-w-5xl pt-20 lg:pt-28">
                <div
                    class="rounded-2xl border border-[#e5e5e7] bg-[#f5f3ff] px-8 py-12 text-center dark:border-[#2a2a2e] dark:bg-[#2a2640] lg:py-16"
                >
                    <h2 class="mb-3 text-xl font-semibold text-[#161d26] dark:text-[#e5e5e7] lg:text-2xl">
                        Ready to digitalize?
                    </h2>
                    <p class="mx-auto max-w-md text-sm text-[#6b6b6e] dark:text-[#9b9b9e]">
                        Create an account, upload a photo or video, and get editable content in seconds with Amazon Nova.
                    </p>
                    <div v-if="!$page.props.auth.user" class="mt-6">
                        <Link
                            :href="register()"
                            class="inline-flex rounded-xl bg-[#7c3aed] px-6 py-3 text-base font-medium text-white transition-colors hover:bg-[#6d28d9] dark:bg-[#8b5cf6] dark:hover:bg-[#7c3aed]"
                        >
                            Create free account
                        </Link>
                    </div>
                    <div v-else class="mt-6">
                        <Link
                            :href="dashboard()"
                            class="inline-flex rounded-xl bg-[#7c3aed] px-6 py-3 text-base font-medium text-white transition-colors hover:bg-[#6d28d9] dark:bg-[#8b5cf6] dark:hover:bg-[#7c3aed]"
                        >
                            Open Dashboard
                        </Link>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
