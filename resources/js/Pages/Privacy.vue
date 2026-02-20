<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout>
        <Head title="Privacy Policy" />

        <div class="bg-white dark:bg-gray-950 min-h-screen">
            <!-- Hero Section -->
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900 text-white py-20">
                <div class="max-w-4xl mx-auto px-6 lg:px-8">
                    <h1 class="text-5xl font-light tracking-tight mb-6">
                        Privacy Policy
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
                            At {{ $page.props.appName }}, we are committed to protecting your privacy and ensuring the security of your personal information.
                            This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website and services.
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            By using our services, you agree to the collection and use of information in accordance with this policy.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">2. Information We Collect</h2>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">2.1 Personal Information</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            We collect information that you provide directly to us when you:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li>Create an account</li>
                            <li>Make a booking or reservation</li>
                            <li>Contact us for support or inquiries</li>
                            <li>Subscribe to our newsletter</li>
                            <li>Participate in surveys or promotions</li>
                        </ul>

                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            This information may include:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li>Full name</li>
                            <li>Email address</li>
                            <li>Phone number</li>
                            <li>Payment information</li>
                            <li>Booking preferences and special requests</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">2.2 Automatically Collected Information</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            When you access our website, we automatically collect certain information, including:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li>IP address</li>
                            <li>Browser type and version</li>
                            <li>Device information</li>
                            <li>Pages visited and time spent on pages</li>
                            <li>Referring website addresses</li>
                            <li>Date and time of access</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">2.3 Cookies and Tracking Technologies</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            We use cookies and similar tracking technologies to track activity on our website and store certain information.
                            You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">3. How We Use Your Information</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            We use the information we collect for various purposes:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400">
                            <li>To process and manage your bookings and reservations</li>
                            <li>To communicate with you about your bookings (confirmations, reminders, updates)</li>
                            <li>To process payments and prevent fraud</li>
                            <li>To provide customer support and respond to your inquiries</li>
                            <li>To send you marketing communications (with your consent)</li>
                            <li>To improve our website, services, and user experience</li>
                            <li>To comply with legal obligations</li>
                            <li>To protect our rights and prevent misuse of our services</li>
                            <li>To analyze usage patterns and trends</li>
                        </ul>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">4. Payment Information</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            We use Paystack as our payment processor. When you make a payment, your payment information is transmitted directly
                            to Paystack and is subject to their privacy policy and security measures.
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            We do not store your complete credit card information on our servers. We only retain the last 4 digits of your card
                            and transaction references for record-keeping purposes.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">5. How We Share Your Information</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            We may share your information in the following circumstances:
                        </p>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">5.1 Service Providers</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            We share information with third-party service providers who perform services on our behalf, including:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li>Payment processors (Paystack)</li>
                            <li>Email service providers</li>
                            <li>Hosting and cloud storage providers</li>
                            <li>Analytics services</li>
                        </ul>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">5.2 Legal Requirements</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            We may disclose your information if required by law or in response to valid requests by public authorities.
                        </p>

                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">5.3 Business Transfers</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            If we are involved in a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">6. Data Security</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            We implement appropriate technical and organizational measures to protect your personal information against
                            unauthorized access, alteration, disclosure, or destruction. These measures include:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li>Encryption of data in transit (SSL/TLS)</li>
                            <li>Secure password hashing</li>
                            <li>Regular security assessments</li>
                            <li>Limited access to personal information</li>
                            <li>Secure data storage practices</li>
                        </ul>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            However, no method of transmission over the internet or electronic storage is 100% secure, and we cannot
                            guarantee absolute security.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">7. Data Retention</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy,
                            unless a longer retention period is required by law. Booking records are retained for accounting and legal purposes
                            for at least 7 years after the completion of your stay.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">8. Your Rights</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            You have certain rights regarding your personal information:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400 mb-6">
                            <li><strong>Access:</strong> You can request a copy of your personal information</li>
                            <li><strong>Correction:</strong> You can request that we correct inaccurate information</li>
                            <li><strong>Deletion:</strong> You can request deletion of your personal information (subject to legal requirements)</li>
                            <li><strong>Opt-out:</strong> You can unsubscribe from marketing communications at any time</li>
                            <li><strong>Data Portability:</strong> You can request your data in a structured, machine-readable format</li>
                            <li><strong>Objection:</strong> You can object to certain processing of your information</li>
                        </ul>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            To exercise these rights, please contact us at info@citystake.net
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">9. Marketing Communications</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            With your consent, we may send you promotional emails about new properties, special offers, and other updates.
                            You can opt out of these communications at any time by:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-400">
                            <li>Clicking the "unsubscribe" link in any marketing email</li>
                            <li>Contacting us directly at info@citystake.net</li>
                            <li>Updating your preferences in your account settings</li>
                        </ul>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">10. Children's Privacy</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Our services are not directed to individuals under the age of 18. We do not knowingly collect personal information
                            from children. If you believe we have collected information from a child, please contact us immediately.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">11. Third-Party Links</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Our website may contain links to third-party websites. We are not responsible for the privacy practices or content
                            of these external sites. We encourage you to read the privacy policies of any third-party sites you visit.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">12. International Data Transfers</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Your information may be transferred to and processed in countries other than Nigeria. We ensure appropriate safeguards
                            are in place to protect your information in accordance with this privacy policy.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">13. Changes to This Privacy Policy</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new
                            policy on this page and updating the "Last updated" date. Your continued use of our services after changes are posted
                            constitutes acceptance of the updated policy.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-3xl font-light text-gray-900 dark:text-white mb-4">14. Contact Us</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:
                        </p>
                        <ul class="list-none space-y-2 text-gray-600 dark:text-gray-400">
                            <li><strong>Email:</strong> info@citystake.net</li>
                            <li><strong>Phone:</strong> +234 801 234 5678</li>
                            <li><strong>Address:</strong> Abuja, Federal Capital Territory, Nigeria</li>
                        </ul>
                    </section>

                    <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-8 text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Your privacy is important to us. We are committed to protecting your personal information and being transparent
                            about our data practices.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="/" class="inline-block px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-all">
                                Return to Homepage
                            </a>
                            <a href="/contact" class="inline-block px-6 py-3 border-2 border-gray-900 dark:border-white text-gray-900 dark:text-white font-medium rounded-full hover:bg-gray-900 hover:text-white dark:hover:bg-white dark:hover:text-gray-900 transition-all">
                                Contact Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
