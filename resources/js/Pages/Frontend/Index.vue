<script setup>
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

const page = usePage();

// ---------- toast ----------
const toastMsg = ref("");
const toastShow = ref(false);
let toastTimer;
function showToast(msg) {
    toastMsg.value = msg;
    toastShow.value = true;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toastShow.value = false), 4500);
}

// ---------- shared validation helpers ----------
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const MOBILE = /^[6-9]\d{9}$/;
const MIN_AGE = 5; // keep in sync with RegistrationController::MIN_AGE

// Local YYYY-MM-DD (toISOString would shift to UTC and can land on the wrong day).
function ymd(d) {
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}
// Latest birth date allowed: today minus MIN_AGE years.
function latestDob() {
    const d = new Date();
    d.setFullYear(d.getFullYear() - MIN_AGE);
    return ymd(d);
}

// Keep phone boxes to digits only (max 10).
function digitsOnly(form, key) {
    watch(
        () => form[key],
        (v) => {
            const clean = String(v ?? "").replace(/\D/g, "").slice(0, 10);
            if (clean !== v) form[key] = clean;
        }
    );
}

// Validator bound to one form: rules return true or an error message.
// Errors live in `errors`, and server-side errors are copied into it too,
// so the template only ever reads one place.
function makeValidator(form, rules) {
    const errors = reactive({});

    const check = (key) => {
        const result = rules[key](form[key]);
        errors[key] = result === true ? "" : result;
        return result === true;
    };

    // Re-validate as the user types, but only once a field is already marked invalid.
    const recheck = (key) => {
        if (errors[key]) check(key);
    };

    const checkAll = () => Object.keys(rules).map(check).every(Boolean);

    const clear = () => Object.keys(errors).forEach((k) => (errors[k] = ""));

    const setServerErrors = (serverErrors) => Object.assign(errors, serverErrors);

    return { errors, check, recheck, checkAll, clear, setServerErrors };
}

// ---------- registration form ----------
const reg = useForm({
    firstName: "",
    lastName: "",
    gender: "",
    email: "",
    whatsapp: "",
    dob: "",
    bloodGroup: "",
    address: "",
    emergency: "",
    medical: "",
    privacy: false,
});
digitsOnly(reg, "whatsapp");
digitsOnly(reg, "emergency");

const regV = makeValidator(reg, {
    firstName: (v) => v.trim().length >= 2 || "Enter your first name.",
    lastName: (v) => v.trim().length >= 1 || "Enter your last name.",
    gender: (v) => !!v || "Select male or female.",
    email: (v) => EMAIL.test(v) || "Enter a valid email, like name@example.com.",
    whatsapp: (v) => MOBILE.test(v) || "Enter a 10 digit mobile number.",
    dob: (v) => {
        if (!v) return "Pick your date of birth from the calendar.";
        return v <= latestDob() || `Participants must be at least ${MIN_AGE} years old.`;
    },
    bloodGroup: (v) => v !== "" || "Select your blood group.",
    address: (v) => v.trim().length >= 6 || "Enter your full address.",
    emergency: (v) => {
        if (v === "") return true;
        if (!MOBILE.test(v)) return "Enter a 10 digit emergency number.";
        return v !== reg.whatsapp || "Emergency contact must be different from your WhatsApp number.";
    },
    medical: (v) => v.trim().length !== 1 || "Write a little more, or leave it blank.",
    privacy: (v) => v === true || "Please accept the Privacy Policy to continue.",
});

const bloodGroups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];

// Calendar stops at the youngest allowed birth date. Set on mount so SSR and the browser agree.
const maxDob = ref("");
const dobInput = ref(null);
function openDobPicker() {
    try {
        dobInput.value?.showPicker();
    } catch (e) {}
}

async function scrollToFirstInvalid() {
    await nextTick();
    document.querySelector("#regForm .invalid")?.scrollIntoView({ behavior: "smooth", block: "center" });
}

function submitRegistration() {
    if (!regV.checkAll()) {
        scrollToFirstInvalid();
        showToast("Please fix the highlighted fields.");
        return;
    }

    // On success the server redirects to the thank-you page (bib + QR).
    reg.post(route("frontend.register"), {
        preserveScroll: (res) => Object.keys(res.props.errors || {}).length > 0,
        onError: (errors) => {
            regV.setServerErrors(errors);
            scrollToFirstInvalid();
            showToast("Please fix the highlighted fields.");
        },
    });
}

// ---------- support ticket popup ----------
const ticket = useForm({ name: "", email: "", phone: "", message: "" });
digitsOnly(ticket, "phone");

const ticketV = makeValidator(ticket, {
    name: (v) => v.trim().length >= 2 || "Enter your name.",
    email: (v) => EMAIL.test(v) || "Enter a valid email, like name@example.com.",
    phone: (v) => MOBILE.test(v) || "Enter a 10 digit phone number.",
    message: (v) => {
        const len = v.trim().length;
        if (!len) return "Tell us what you need help with.";
        if (len < 10) return "Please describe your query in a little more detail.";
        return len <= 2000 || "Please keep it under 2000 characters.";
    },
});

const ticketModal = ref(null);
const ticketName = ref(null);

function openTicket() {
    ticketV.clear();
    document.body.classList.add("modal-open");
    ticketModal.value.showModal();
    ticketName.value.focus();
}
function closeTicket() {
    ticketModal.value?.close();
}
function onTicketClosed() {
    document.body.classList.remove("modal-open");
}
// A click on the dark area outside the box closes it.
function onModalClick(e) {
    if (e.target === ticketModal.value) closeTicket();
}

async function focusFirstTicketError() {
    await nextTick();
    ticketModal.value?.querySelector(".invalid input, .invalid textarea")?.focus();
}

function submitTicket() {
    if (!ticketV.checkAll()) {
        focusFirstTicketError();
        return;
    }

    ticket.post(route("frontend.support-ticket"), {
        preserveScroll: true,
        onSuccess: () => {
            ticket.reset();
            closeTicket();
            showToast(page.props.flash?.success || "Thank you! Your support ticket has been submitted. We will contact you soon.");
        },
        onError: (errors) => {
            ticketV.setServerErrors(errors);
            focusFirstTicketError();
        },
    });
}

onMounted(() => {
    maxDob.value = latestDob();
});
onBeforeUnmount(() => {
    clearTimeout(toastTimer);
    document.body.classList.remove("modal-open");
});
</script>

<template>
    <!-- hero section start -->
    <section class="hero">
        <div class="hero-text">
            <p class="presented">NephroCare in association with Vivacity Multispeciality Hospital</p>
            <h1>
                <span class="h-annual">Annual</span>
                <span class="h-walk">Walkathon</span>
                <span class="year"><b>2026</b></span>
            </h1>
            <p class="tagline">Walk for Kidney, Walk for Health</p>
            <p class="lead">A 4 km walk through Salt Lake with around 5,000 people from across Kolkata. Bring your family and walk with us.</p>
            <div class="cta-row">
                <a class="btn gold" href="#register">Register now
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></a>
                <a class="btn outline" href="#details">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s7-6.300 7-12a7 7 0 00-14 0c0 5.700 7 12 7 12z" /><circle cx="12" cy="10" r="2.600" /></svg>See route &amp; details</a>
            </div>
            <ul class="stats">
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="14" cy="4.500" r="2" /><path d="M13 8.500l-3 4 3 3 1 5.500M10 12.500l-4 1.500M13 8.500l3 3.500 3.500.5M13 15.500l-4 4" /></svg>
                    <span><b>4 km</b><small>Walk</small></span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" /><circle cx="5.500" cy="9.500" r="2.200" /><circle cx="18.500" cy="9.500" r="2.200" /><path d="M6.500 20c0-3.200 2.500-5.200 5.500-5.200s5.500 2 5.500 5.200zM1.500 17c0-2 1.400-3.500 3.500-3.500M22.500 17c0-2-1.400-3.500-3.500-3.500" /></svg>
                    <span><b>5,000+</b><small>Participants</small></span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.500" y="5" width="17" height="15.500" rx="2" /><path d="M3.500 10h17M8 3v4M16 3v4M8 13.500h2M14 13.500h2M8 16.800h2M14 16.800h2" /></svg>
                    <span><b>13 Dec 2026</b><small>Salt Lake, Kolkata</small></span>
                </li>
            </ul>
        </div>

        <div class="hero-visual">
            <img src="/images/hero-photo.jpg" alt="Smiling walkers in Nephrocare Annual Walkathon T-shirts walking through a green park, with two smaller photos of the crowd and a T-shirt back" width="1024" height="745" />
            <div class="badge" aria-label="4 kilometre walk">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="15" cy="4.500" r="2" /><path d="M14 8.500l-3.500 4 3 3 1 5M10.500 12.500l-4.500 1.500M14 8.500l3 3.500 3.500.5M13.500 15.500l-4 4M2 9h4M3 12h2.500" /></svg>
                <b>4 km</b><span>Walk</span>
            </div>
        </div>
    </section>
    <!-- hero section end -->

    <!-- about section start -->
    <section class="about" id="about">
        <div class="wrap two">
            <div class="about-photo">
                <img src="/images/about-photo.jpg" alt="Two smiling participants in Nephrocare Annual Walkathon T-shirts" loading="lazy" />
            </div>
            <div class="about-text">
                <p class="d-eyebrow"><span>About the initiative</span></p>
                <h2>Why we are <em>walking</em></h2>
                <div class="rule"></div>
                <p>Walking is the easiest exercise there is. It needs no gym and no kit, and it helps keep your heart, weight and blood sugar in check.</p>
                <p>Diabetes and high blood pressure are two of the leading causes of kidney disease. Regular walking helps manage both, so a simple daily walk is also kidney care.</p>
                <p>This walkathon brings the city together to talk about early check-ups, prevention and a more active lifestyle.</p>
                <div class="who">
                    <div class="who-head">
                        <span class="who-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="5" r="1.800" /><circle cx="16.500" cy="5" r="1.800" /><path d="M8 9v5.500l-2.500 5.500M8 14.500l2.500 5.500M5 12l3-3 3 2.500M16.500 9v5.500L14 20M16.500 14.500l2.500 5.500M13.500 12l3-3 3 2.500" /></svg></span>
                        <h3>Who is walking?</h3>
                    </div>
                    <ul>
                        <li>Doctors and healthcare professionals</li>
                        <li>Fitness enthusiasts and students</li>
                        <li>Citizens from Kolkata and nearby areas</li>
                        <li>Patients and their families</li>
                        <li>Corporate teams and community groups</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- about section end -->

    <!-- details section start -->
    <section class="details" id="details">
        <div class="wrap">
            <header class="d-head">
                <p class="d-eyebrow"><span>Plan your day</span></p>
                <h2>Event <em>Details</em></h2>
                <p class="d-sub">Get all the important information about the event at a glance.</p>
            </header>

            <div class="cards">
                <article class="card">
                    <span class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M3 10h18M8 3v4M16 3v4M8 14.500h2M14 14.500h2M8 17.500h2" /></svg></span>
                    <div class="txt"><p class="label">Event</p><p class="value">Annual Walkathon 2026</p></div>
                </article>
                <article class="card">
                    <span class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M3 10h18M8 3v4M16 3v4M8 14.500h2M14 14.500h2M8 17.500h2" /></svg></span>
                    <div class="txt"><p class="label">Date</p><p class="value">Sunday, 13 December 2026</p></div>
                </article>
                <article class="card gold">
                    <span class="ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3" /><circle cx="5.500" cy="9.500" r="2.200" /><circle cx="18.500" cy="9.500" r="2.200" /><path d="M6.500 20c0-3.200 2.500-5.200 5.500-5.200s5.500 2 5.500 5.200zM1.500 17c0-2 1.400-3.500 3.500-3.500M22.500 17c0-2-1.400-3.500-3.500-3.500" /></svg></span>
                    <div class="txt"><p class="label">Organiser</p><p class="value">NephroCare India Ltd.</p></div>
                </article>
                <article class="card">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M12 15s5-4.500 5-8.500a5 5 0 00-10 0C7 10.500 12 15 12 15z" /><circle cx="12" cy="6.500" r="1.800" /><circle cx="4.500" cy="19.500" r="1.500" /><path d="M8 19.500h3M13.500 19.500h2M18 19.500h2" /></svg></span>
                    <div class="txt"><p class="label">Distance</p><p class="value">4 Kilometres</p></div>
                </article>
            </div>

            <div class="route" aria-label="Route: 2 kilometres from Salt Lake Stadium to Salt Lake Central Park, then 2 kilometres back to Salt Lake Stadium.">
                <div class="stop"><i></i><div><b>Start</b><span>Salt Lake Stadium</span></div></div>
                <div class="line"><span>2 km</span></div>
                <div class="stop mid"><i class="mid"></i><div><b>Turnaround</b><span>Salt Lake Central Park</span></div></div>
                <div class="line"><span>2 km</span></div>
                <div class="stop right"><i class="end"></i><div><b>Finish</b><span>Salt Lake Stadium</span></div></div>
            </div>

            <!-- TODO: confirm the entitlements and the reporting details with the organisers before going live -->
            <div class="perks">
                <h3 class="perks-title">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="9" width="18" height="4" rx="1" /><path d="M5 13v8h14v-8M12 9v12M12 9c-2.500 0-4.500-1-4.500-2.800S9 3.500 10.200 4.500C11.200 5.300 12 9 12 9zM12 9c2.500 0 4.500-1 4.500-2.800S15 3.500 13.800 4.500C12.800 5.300 12 9 12 9z" /></svg>
                    Participant's Entitlements
                </h3>
                <ul class="perk-list">
                    <li>
                        <span class="p-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3L3 6l2 4 3-1v12h8V9l3 1 2-4-5-3c-.5 1.800-2 2.800-4 2.800S8.500 4.800 8 3z" /></svg></span>
                        <span>Event T-shirt</span>
                    </li>
                    <li>
                        <span class="p-ico gold"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="15" r="5.500" /><path d="M8.500 10.500L6 3h4l2 4 2-4h4l-2.500 7.500M12 12.500v5M10.500 14l1.500-1.500" /></svg></span>
                        <span>Finisher Medal</span>
                    </li>
                    <li>
                        <span class="p-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3s6 6.500 6 11a6 6 0 01-12 0c0-4.500 6-11 6-11z" /><path d="M9.500 14.500a2.500 2.500 0 002.500 2.500" /></svg></span>
                        <span>Hydration stations en route</span>
                    </li>
                    <li>
                        <span class="p-ico gold"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h13v6a5 5 0 01-5 5H9a5 5 0 01-5-5zM17 9.500h1.500a2.500 2.500 0 010 5H17M7 3v2.500M10.500 3v2.500M14 3v2.500" /></svg></span>
                        <span>Refreshments</span>
                    </li>
                </ul>
                <p class="perks-note">Reporting time and bib collection details will be shared by email and WhatsApp after you register.</p>
            </div>
        </div>
    </section>
    <!-- details section end -->

    <!-- banner section start -->
    <section class="banner">
        <img src="/images/walkers-main.jpg" alt="" loading="lazy" />
        <div class="banner-text">
            <h2>Every step counts.</h2>
            <p>Join thousands of Kolkata walkers for healthier kidneys and healthier lives.</p>
            <a class="btn gold" href="#register">Reserve your spot</a>
        </div>
    </section>
    <!-- banner section end -->

    <!-- register section start -->
    <section class="register" id="register">
        <div class="wrap reg-grid">
            <div class="reg-intro">
                <p class="eyebrow">Join us</p>
                <h2>Register to walk</h2>
                <p>Fill in the form and we will send your confirmation on WhatsApp and email. Fields marked <span class="req">*</span> are required.</p>
                <p class="small">We ask for blood group, emergency contact and medical history so our medical team can help you quickly if something happens on the day.</p>
                <img class="reg-photo" src="/images/walkers-group.jpg" alt="Participants walking together" loading="lazy" />
            </div>

            <form id="regForm" novalidate @submit.prevent="submitRegistration">
                <div class="row">
                    <div class="field" :class="{ invalid: regV.errors.firstName }">
                        <label for="fn">First name <span class="req">*</span></label>
                        <input id="fn" v-model="reg.firstName" name="firstName" type="text" autocomplete="given-name" required
                            @blur="regV.check('firstName')" @input="regV.recheck('firstName')" />
                        <small class="err">{{ regV.errors.firstName }}</small>
                    </div>
                    <div class="field" :class="{ invalid: regV.errors.lastName }">
                        <label for="ln">Last name <span class="req">*</span></label>
                        <input id="ln" v-model="reg.lastName" name="lastName" type="text" autocomplete="family-name" required
                            @blur="regV.check('lastName')" @input="regV.recheck('lastName')" />
                        <small class="err">{{ regV.errors.lastName }}</small>
                    </div>
                </div>

                <fieldset class="field" :class="{ invalid: regV.errors.gender }">
                    <legend>Gender <span class="req">*</span></legend>
                    <label class="radio"><input v-model="reg.gender" type="radio" name="gender" value="Male" @change="regV.check('gender')" /> Male</label>
                    <label class="radio"><input v-model="reg.gender" type="radio" name="gender" value="Female" @change="regV.check('gender')" /> Female</label>
                    <small class="err">{{ regV.errors.gender }}</small>
                </fieldset>

                <div class="field" :class="{ invalid: regV.errors.email }">
                    <label for="email">Email <span class="req">*</span></label>
                    <input id="email" v-model="reg.email" name="email" type="email" autocomplete="email" required
                        @blur="regV.check('email')" @input="regV.recheck('email')" />
                    <small class="err">{{ regV.errors.email }}</small>
                </div>

                <div class="row">
                    <div class="field" :class="{ invalid: regV.errors.whatsapp }">
                        <label for="wa">WhatsApp number <span class="req">*</span></label>
                        <input id="wa" v-model="reg.whatsapp" name="whatsapp" type="tel" inputmode="numeric" maxlength="10" placeholder="10 digit number" required
                            @blur="regV.check('whatsapp')" @input="regV.recheck('whatsapp'); regV.recheck('emergency')" />
                        <small class="err">{{ regV.errors.whatsapp }}</small>
                    </div>
                    <div class="field" :class="{ invalid: regV.errors.dob }">
                        <label for="dob">Date of birth <span class="req">*</span></label>
                        <input id="dob" ref="dobInput" v-model="reg.dob" name="dob" type="date" :max="maxDob || null" required
                            @click="openDobPicker" @blur="regV.check('dob')" @input="regV.recheck('dob')" />
                        <small class="err">{{ regV.errors.dob }}</small>
                    </div>
                </div>

                <div class="field" :class="{ invalid: regV.errors.bloodGroup }">
                    <label for="blood">Blood group <span class="req">*</span></label>
                    <select id="blood" v-model="reg.bloodGroup" name="bloodGroup" required
                        @blur="regV.check('bloodGroup')" @change="regV.recheck('bloodGroup')">
                        <option value="">Select blood group</option>
                        <option v-for="g in bloodGroups" :key="g" :value="g">{{ g }}</option>
                    </select>
                    <small class="err">{{ regV.errors.bloodGroup }}</small>
                </div>

                <div class="field" :class="{ invalid: regV.errors.address }">
                    <label for="addr">Address <span class="req">*</span></label>
                    <textarea id="addr" v-model="reg.address" name="address" rows="2" autocomplete="street-address" required
                        @blur="regV.check('address')" @input="regV.recheck('address')"></textarea>
                    <small class="err">{{ regV.errors.address }}</small>
                </div>

                <div class="field" :class="{ invalid: regV.errors.emergency }">
                    <label for="emg">Emergency contact number</label>
                    <input id="emg" v-model="reg.emergency" name="emergency" type="tel" inputmode="numeric" maxlength="10" placeholder="Someone we can call on the day"
                        @blur="regV.check('emergency')" @input="regV.recheck('emergency')" />
                    <small class="err">{{ regV.errors.emergency }}</small>
                </div>

                <div class="field" :class="{ invalid: regV.errors.medical }">
                    <label for="med">Any medical history</label>
                    <textarea id="med" v-model="reg.medical" name="medical" rows="3" placeholder="Allergies, surgeries, medicines, conditions. Leave blank if there is nothing."
                        @blur="regV.check('medical')" @input="regV.recheck('medical')"></textarea>
                    <small class="err">{{ regV.errors.medical }}</small>
                </div>

                <div class="field consent" :class="{ invalid: regV.errors.privacy }">
                    <label class="check" for="privacy">
                        <input id="privacy" v-model="reg.privacy" name="privacy" type="checkbox" required @change="regV.check('privacy')" />
                        <span>I have read and agree to the <a :href="route('frontend.privacy')" target="_blank" rel="noopener">Privacy Policy</a>. By registering, I confirm that the details provided above are correct and that I am physically fit to participate in a 4 km walk.</span>
                    </label>
                    <small class="err">{{ regV.errors.privacy }}</small>
                </div>

                <button class="btn full" type="submit" :disabled="reg.processing">
                    {{ reg.processing ? "Submitting..." : "Complete registration" }}
                </button>
            </form>
        </div>
    </section>
    <!-- register section end -->

    <!-- faq section start -->
    <section class="faq" id="faq">
        <div class="wrap narrow">
            <header class="faq-head">
                <p class="faq-pill"><span>FAQ</span></p>
                <h2>Good to <em>know</em></h2>
                <p class="faq-sub">Find quick answers to common questions about the walkathon.</p>
            </header>

            <div class="faq-list">
                <details>
                    <summary>
                        <span class="q-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.500" /><path d="M5 20c0-3.900 3-6.500 7-6.500s7 2.600 7 6.500" /></svg></span>
                        <span class="q-text">Do I need to be a runner?</span>
                        <span class="chev" aria-hidden="true"></span>
                    </summary>
                    <p>No. It is a 4 km walk at your own pace. Children and older walkers are welcome with a family member.</p>
                </details>
                <details>
                    <summary>
                        <span class="q-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6z" /><path d="M14.500 3v4.500H19M9 12h7M9 15.500h7M9 8.500h2.500" /></svg></span>
                        <span class="q-text">Is the registration free?</span>
                        <span class="chev" aria-hidden="true"></span>
                    </summary>
                    <p>Yes. Registration for the Annual Walkathon 2026 is completely free. Just fill in the form and walk with us.</p>
                </details>
                <details>
                    <summary>
                        <span class="q-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.500" y="5" width="17" height="15.500" rx="2" /><path d="M3.500 10h17M8 3v4M16 3v4M8 13.500h2M14 13.500h2M8 16.800h2M14 16.800h2" /></svg></span>
                        <span class="q-text">What if I feel unwell on the day?</span>
                        <span class="chev" aria-hidden="true"></span>
                    </summary>
                    <p>Stop, sit down and tell the nearest volunteer. A medical team will be around the route.</p>
                </details>
                <details>
                    <summary>
                        <span class="q-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 9.200a3 3 0 115 2c-1.200.9-2 1.400-2 3M12 18v.2" /></svg></span>
                        <span class="q-text">Who do I contact for help?</span>
                        <span class="chev" aria-hidden="true"></span>
                    </summary>
                    <p>Need Help contact <a href="tel:+916292343601">+91 62923 43601</a></p>
                </details>
            </div>
        </div>
    </section>
    <!-- faq section end -->

    <!-- contact section start -->
    <section class="contact" id="contact">
        <div class="wrap contact-grid">
            <div class="c-card help">
                <span class="c-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14v-2a8 8 0 0116 0v2" /><rect x="3" y="13" width="4" height="6" rx="1.500" /><rect x="17" y="13" width="4" height="6" rx="1.500" /><path d="M19 19c0 1.700-1.800 2.500-4 2.500h-2" /></svg></span>
                <h3>Have a question related to the event?</h3>
                <p class="ask">Ask the organiser</p>
                <p class="org">NephroCare India Ltd.</p>
                <a class="phone" href="tel:+916292343601">+91 62923 43601</a>
                <p class="hours">Mon-Fri (10 am to 6 pm)</p>
            </div>

            <div class="c-card ticket">
                <span class="c-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9a2 2 0 002-2V6h14v1a2 2 0 000 4v2a2 2 0 000 4v1H5v-1a2 2 0 00-2-2z" transform="translate(0 -1)" /><path d="M10 9.500h4M10 13h4" /></svg></span>
                <h3>Have an issue during registration?</h3>
                <p class="ask">Our team will get back to you as soon as possible.</p>
                <button class="btn gold" type="button" aria-haspopup="dialog" @click="openTicket">Raise a Support Ticket</button>
            </div>
        </div>
    </section>
    <!-- contact section end -->

    <!-- support ticket popup -->
    <dialog ref="ticketModal" class="modal" aria-labelledby="ticketTitle" @close="onTicketClosed" @click="onModalClick">
        <form novalidate @submit.prevent="submitTicket">
            <button class="m-close" type="button" aria-label="Close" @click="closeTicket">&times;</button>
            <span class="c-ico m-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9a2 2 0 002-2V6h14v1a2 2 0 000 4v2a2 2 0 000 4v1H5v-1a2 2 0 00-2-2z" transform="translate(0 -1)" /><path d="M10 9.500h4M10 13h4" /></svg></span>
            <h3 id="ticketTitle">Raise a Support Ticket</h3>
            <p class="m-sub">Share your details and our team will get back to you as soon as possible.</p>

            <div class="field" :class="{ invalid: ticketV.errors.name }">
                <label for="tn">Name <span class="req">*</span></label>
                <input id="tn" ref="ticketName" v-model="ticket.name" name="name" type="text" autocomplete="name" required
                    @blur="ticketV.check('name')" @input="ticketV.recheck('name')" />
                <small class="err">{{ ticketV.errors.name }}</small>
            </div>
            <div class="field" :class="{ invalid: ticketV.errors.email }">
                <label for="te">Email <span class="req">*</span></label>
                <input id="te" v-model="ticket.email" name="email" type="email" autocomplete="email" required
                    @blur="ticketV.check('email')" @input="ticketV.recheck('email')" />
                <small class="err">{{ ticketV.errors.email }}</small>
            </div>
            <div class="field" :class="{ invalid: ticketV.errors.phone }">
                <label for="tp">Phone number <span class="req">*</span></label>
                <input id="tp" v-model="ticket.phone" name="phone" type="tel" inputmode="numeric" maxlength="10" placeholder="10 digit number" autocomplete="tel-national" required
                    @blur="ticketV.check('phone')" @input="ticketV.recheck('phone')" />
                <small class="err">{{ ticketV.errors.phone }}</small>
            </div>
            <div class="field" :class="{ invalid: ticketV.errors.message }">
                <label for="tm">Message <span class="req">*</span></label>
                <textarea id="tm" v-model="ticket.message" name="message" rows="4" maxlength="2000" placeholder="Describe your query or the issue you faced in detail." required
                    @blur="ticketV.check('message')" @input="ticketV.recheck('message')"></textarea>
                <small class="err">{{ ticketV.errors.message }}</small>
            </div>

            <button class="btn full" type="submit" :disabled="ticket.processing">
                {{ ticket.processing ? "Submitting..." : "Submit" }}
            </button>
        </form>
    </dialog>

    <div class="toast" :class="{ show: toastShow }" role="status">{{ toastMsg }}</div>
</template>
