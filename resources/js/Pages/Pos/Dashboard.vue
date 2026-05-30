<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>

    <AppLayout>

        <div class="space-y-6">

            <!-- ===================================================== -->
            <!-- HEADER -->
            <!-- ===================================================== -->

            <div class="flex items-center justify-between">

                <div>

                    <h1
                        class="
                            text-3xl
                            font-black
                            text-slate-800
                            dark:text-slate-100
                        "
                    >
                        Dashboard
                    </h1>

                    <p
                        class="
                            mt-1
                            text-slate-500
                            dark:text-slate-400
                        "
                    >
                        Welcome back to NexusFlow POS
                    </p>

                </div>

                <button
                    class="
                        bg-emerald-500
                        hover:bg-emerald-600
                        text-white
                        rounded-2xl
                        px-5
                        py-3
                        font-semibold
                        transition-all
                    "
                >
                    New Sale
                </button>

            </div>


            <!-- ===================================================== -->
            <!-- KPI CARDS -->
            <!-- ===================================================== -->

            <div
                class="
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    xl:grid-cols-4
                    gap-6
                "
            >

                <StatsCard
                    title="Today's Sales"
                    value="$24,500"
                    icon="💰"
                    percentage="+12.5%"
                />

                <StatsCard
                    title="Transactions"
                    value="325"
                    icon="🧾"
                    color="blue"
                    percentage="+8.1%"
                />

                <StatsCard
                    title="Customers"
                    value="1,240"
                    icon="👥"
                    color="purple"
                    percentage="+4.3%"
                />

                <StatsCard
                    title="Low Stock"
                    value="18"
                    icon="⚠️"
                    color="red"
                />

            </div>


            <!-- ===================================================== -->
            <!-- MAIN GRID -->
            <!-- ===================================================== -->

            <div
                class="
                    grid
                    grid-cols-1
                    xl:grid-cols-3
                    gap-6
                "
            >

                <!-- SALES OVERVIEW -->

                <div
                    class="
                        xl:col-span-2
                        bg-white
                        dark:bg-slate-950
                        rounded-3xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        p-6
                        shadow-sm
                        transition-colors
                    "
                >

                    <div class="flex items-center justify-between mb-6">

                        <div>

                            <h3
                                class="
                                    font-bold
                                    text-lg
                                    text-slate-800
                                    dark:text-slate-100
                                "
                            >
                                Sales Overview
                            </h3>

                            <p
                                class="
                                    text-sm
                                    text-slate-500
                                    dark:text-slate-400
                                "
                            >
                                Weekly revenue analytics
                            </p>

                        </div>

                    </div>

                    <div
                        class="
                            h-[350px]
                            flex
                            items-center
                            justify-center
                            text-slate-400
                            dark:text-slate-500
                        "
                    >
                        Chart Coming Soon
                    </div>

                </div>


                <!-- STORE STATUS -->

                <div
                    class="
                        bg-white
                        dark:bg-slate-950
                        rounded-3xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        p-6
                        shadow-sm
                        transition-colors
                    "
                >

                    <h3
                        class="
                            font-bold
                            text-lg
                            mb-6
                            text-slate-800
                            dark:text-slate-100
                        "
                    >
                        Store Status
                    </h3>

                    <div class="space-y-5">

                        <div class="flex items-center justify-between">

                            <span
                                class="
                                    text-sm
                                    text-slate-500
                                    dark:text-slate-400
                                "
                            >
                                Active Register
                            </span>

                            <span class="font-bold text-emerald-500">
                                OPEN
                            </span>

                        </div>

                        <div class="flex items-center justify-between">

                            <span
                                class="
                                    text-sm
                                    text-slate-500
                                    dark:text-slate-400
                                "
                            >
                                Current Branch
                            </span>

                            <span
                                class="
                                    font-semibold
                                    text-slate-800
                                    dark:text-slate-100
                                "
                            >
                                Main Branch
                            </span>

                        </div>

                        <div class="flex items-center justify-between">

                            <span
                                class="
                                    text-sm
                                    text-slate-500
                                    dark:text-slate-400
                                "
                            >
                                Employees Online
                            </span>

                            <span
                                class="
                                    font-semibold
                                    text-slate-800
                                    dark:text-slate-100
                                "
                            >
                                8 Users
                            </span>

                        </div>

                        <div class="flex items-center justify-between">

                            <span
                                class="
                                    text-sm
                                    text-slate-500
                                    dark:text-slate-400
                                "
                            >
                                Pending Orders
                            </span>

                            <span class="font-semibold text-orange-500">
                                14
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ===================================================== -->
            <!-- TABLES -->
            <!-- ===================================================== -->

            <div
                class="
                    grid
                    grid-cols-1
                    xl:grid-cols-2
                    gap-6
                "
            >

                <!-- RECENT SALES -->

                <div
                    class="
                        bg-white
                        dark:bg-slate-950
                        rounded-3xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        p-6
                        shadow-sm
                        transition-colors
                    "
                >

                    <div class="flex items-center justify-between mb-6">

                        <h3
                            class="
                                font-bold
                                text-lg
                                text-slate-800
                                dark:text-slate-100
                            "
                        >
                            Recent Sales
                        </h3>

                        <button
                            class="
                                text-sm
                                text-emerald-500
                                font-semibold
                            "
                        >
                            View All
                        </button>

                    </div>

                    <div class="space-y-4">

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                dark:border-slate-800
                                pb-4
                            "
                        >

                            <div>

                                <h4
                                    class="
                                        font-semibold
                                        text-sm
                                        text-slate-800
                                        dark:text-slate-100
                                    "
                                >
                                    Ticket #000124
                                </h4>

                                <p
                                    class="
                                        text-xs
                                        text-slate-500
                                        dark:text-slate-400
                                    "
                                >
                                    John Doe
                                </p>

                            </div>

                            <span class="font-bold text-emerald-500">
                                $240.00
                            </span>

                        </div>

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                dark:border-slate-800
                                pb-4
                            "
                        >

                            <div>

                                <h4
                                    class="
                                        font-semibold
                                        text-sm
                                        text-slate-800
                                        dark:text-slate-100
                                    "
                                >
                                    Ticket #000125
                                </h4>

                                <p
                                    class="
                                        text-xs
                                        text-slate-500
                                        dark:text-slate-400
                                    "
                                >
                                    Jane Smith
                                </p>

                            </div>

                            <span class="font-bold text-emerald-500">
                                $120.00
                            </span>

                        </div>

                    </div>

                </div>


                <!-- LOW STOCK -->

                <div
                    class="
                        bg-white
                        dark:bg-slate-950
                        rounded-3xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        p-6
                        shadow-sm
                        transition-colors
                    "
                >

                    <div class="flex items-center justify-between mb-6">

                        <h3
                            class="
                                font-bold
                                text-lg
                                text-slate-800
                                dark:text-slate-100
                            "
                        >
                            Low Stock Products
                        </h3>

                        <button
                            class="
                                text-sm
                                text-red-500
                                font-semibold
                            "
                        >
                            Restock
                        </button>

                    </div>

                    <div class="space-y-4">

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                dark:border-slate-800
                                pb-4
                            "
                        >

                            <div>

                                <h4
                                    class="
                                        font-semibold
                                        text-sm
                                        text-slate-800
                                        dark:text-slate-100
                                    "
                                >
                                    Coca Cola 600ml
                                </h4>

                                <p
                                    class="
                                        text-xs
                                        text-slate-500
                                        dark:text-slate-400
                                    "
                                >
                                    SKU: 10024
                                </p>

                            </div>

                            <span class="text-red-500 font-bold">
                                2 left
                            </span>

                        </div>

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                dark:border-slate-800
                                pb-4
                            "
                        >

                            <div>

                                <h4
                                    class="
                                        font-semibold
                                        text-sm
                                        text-slate-800
                                        dark:text-slate-100
                                    "
                                >
                                    Doritos Nacho
                                </h4>

                                <p
                                    class="
                                        text-xs
                                        text-slate-500
                                        dark:text-slate-400
                                    "
                                >
                                    SKU: 20455
                                </p>

                            </div>

                            <span class="text-red-500 font-bold">
                                4 left
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </AppLayout>

</template>
