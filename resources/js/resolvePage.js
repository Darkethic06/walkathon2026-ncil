import { defineAsyncComponent } from "vue";

// Shared by app.js (browser) and ssr.js (Node) so both pick the same layout.
// Layout is chosen purely by page-name prefix:
//   Admin/*  -> AuthenticatedLayout
//   Auth/*   -> none (the page wraps itself in GuestLayout)
//   anything else -> Frontend Layout (walkathon header/footer)

const AdminLayout = defineAsyncComponent(() =>
    import("./Layouts/AuthenticatedLayout.vue")
);

const FrontendLayout = defineAsyncComponent(() =>
    import("./Layouts/Frontend/Layout.vue")
);

const pages = import.meta.glob("./Pages/**/*.vue", { eager: false });

export async function resolvePage(name) {
    const page = await pages[`./Pages/${name}.vue`]();

    if (name.startsWith("Admin/")) {
        page.default.layout = AdminLayout;
    } else if (!name.startsWith("Auth/")) {
        page.default.layout = FrontendLayout;
    }

    return page;
}
