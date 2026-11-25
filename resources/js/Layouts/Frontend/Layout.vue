<script setup>
import "../../../css/walkathon.css";
import Header from "./Header.vue";
import Footer from "./Footer.vue";
import SeoHead from "../../Components/SeoHead.vue";
</script>

<template>
    <SeoHead />
    <Header />
    <slot />
    <Footer />
</template>
