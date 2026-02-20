<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout>
        <Head title="Terms & Conditions" />

        <div class="bg-white dark:bg-gray-950 min-h-screen">
            <!-- Hero Section -->
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900 text-white py-20">
                <div class="max-w-4xl mx-auto px-6 lg:px-8">
                    <h1 class="text-5xl font-light tracking-tight mb-6">
                        Terms & Conditions
                    </h1>
                    <p class="text-xl text-gray-300">
                        Last updated: {{ new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </p>
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-4xl mx-auto px-6 lg:px-8 py-20">
                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">1. Introduction</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            Welcome to {{ $page.props.appName }}. These Terms and Conditions govern your use of our website and booking services.
                            By accessing or using our services, you agree to be bound by these terms.
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            If you do not agree with any part of these terms, you may not use our services.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">2. Booking and Reservations</h2>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">2.1 Making a Reservation</h3>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li>All bookings are subject to availability</li>
                            <li>A valid email address and phone number are required</li>
                            <li>Full payment is required at the time of booking</li>
                            <li>You will receive a booking confirmation via email</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">2.2 Booking Confirmation</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            Once payment is confirmed, you will receive a booking confirmation email with your booking reference number,
                            property details, and check-in instructions.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">3. Payment Terms</h2>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">3.1 Payment Methods</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            We accept payments via Paystack, which supports debit cards, credit cards, and bank transfers.
                        </p>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">3.2 Pricing</h3>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li>All prices are displayed in Nigerian Naira (₦)</li>
                            <li>Prices include applicable taxes unless stated otherwise</li>
                            <li>Additional fees (cleaning fee, service charge) are clearly shown before payment</li>
                            <li>Prices are subject to change without notice</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">3.3 Payment Security</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            All payment transactions are processed securely through Paystack. We do not store your credit card information.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">4. Cancellation and Refund Policy</h2>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">4.1 Guest Cancellations</h3>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li><strong>More than 7 days before check-in:</strong> Full refund minus processing fees</li>
                            <li><strong>3-7 days before check-in:</strong> 50% refund</li>
                            <li><strong>Less than 3 days before check-in:</strong> No refund</li>
                            <li><strong>After check-in:</strong> No refund for unused nights</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">4.2 Host Cancellations</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            If we cancel your confirmed booking, you will receive a full refund including all fees.
                            We will also assist you in finding alternative accommodation if possible.
                        </p>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">4.3 Refund Processing</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Approved refunds will be processed within 5-7 business days to your original payment method.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">5. Check-in and Check-out</h2>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">5.1 Check-in</h3>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li>Standard check-in time: 2:00 PM</li>
                            <li>Valid government-issued ID required</li>
                            <li>Early check-in may be available upon request (subject to availability)</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">5.2 Check-out</h3>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400">
                            <li>Standard check-out time: 12:00 PM (noon)</li>
                            <li>Late check-out may be available upon request (additional charges may apply)</li>
                            <li>Please leave the property in good condition</li>
                        </ul>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">6. Guest Responsibilities</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">As a guest, you agree to:</p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400">
                            <li>Provide accurate information during booking</li>
                            <li>Treat the property with respect and care</li>
                            <li>Comply with maximum occupancy limits</li>
                            <li>Follow house rules and building regulations</li>
                            <li>Not engage in illegal activities on the premises</li>
                            <li>Not sublet or transfer the booking to another party</li>
                            <li>Be responsible for any damage caused during your stay</li>
                            <li>Maintain reasonable noise levels and respect neighbors</li>
                        </ul>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">7. Property Damage and Liability</h2>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">7.1 Damage to Property</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            Guests are responsible for any damage to the property or its contents caused during their stay.
                            You will be charged for repairs or replacements at fair market value.
                        </p>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">7.2 Personal Belongings</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            We are not responsible for loss, theft, or damage to personal belongings.
                            Please ensure your valuables are secured.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">8. Force Majeure</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            We shall not be liable for any failure to perform our obligations due to circumstances beyond our reasonable control,
                            including but not limited to acts of God, natural disasters, war, civil unrest, government actions, or public health emergencies.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">9. Limitation of Liability</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Our total liability to you for any claims arising from your use of our services shall not exceed the amount
                            you paid for the relevant booking.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">10. Privacy</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Your use of our services is also governed by our <a href="/privacy" class="text-blue-600 dark:text-blue-400 hover:underline">Privacy Policy</a>.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">11. Modifications to Terms</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting.
                            Your continued use of our services after changes are posted constitutes acceptance of the modified terms.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">12. Governing Law</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            These terms shall be governed by and construed in accordance with the laws of the Federal Republic of Nigeria.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">13. Contact Information</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            If you have any questions about these Terms and Conditions, please contact us:
                        </p>
                        <ul class="list-none space-y-2 text-gray-600 dark:text-gray-400">
                            <li><strong>Email:</strong> info@citystake.net</li>
                            <li><strong>Phone:</strong> +234 801 234 5678</li>
                            <li><strong>Address:</strong> Abuja, Federal Capital Territory, Nigeria</li>
                        </ul>
                    </section>

                    <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-8 text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            By using {{ $page.props.appName }}, you acknowledge that you have read, understood,
                            and agree to be bound by these Terms and Conditions.
                        </p>
                        <a href="/" class="inline-block px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-all">
                            Return to Homepage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
