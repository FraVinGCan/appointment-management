import { createRouter, createWebHistory } from "vue-router";

import { useAuthStore } from "@/stores/auth";

import Home from "@/pages/Home.vue";
import Login from "@/pages/auth/Login.vue";
import Register from "@/pages/auth/Register.vue";
import Marketplace from "@/pages/services/Marketplace.vue";
import BookAppointment from "@/pages/appointments/BookAppointment.vue";
import ClientAppointments from "@/pages/appointments/ClientAppointments.vue";
import AdminAppointments from "@/pages/appointments/AdminAppointments.vue";
import AppointmentCreate from "@/pages/appointments/AppointmentCreate.vue";
import AppointmentDetails from "@/pages/appointments/AppointmentDetails.vue";
import AppointmentEdit from "@/pages/appointments/AppointmentEdit.vue";
import ClientList from "@/pages/clients/ClientList.vue";
import ClientCreate from "@/pages/clients/ClientCreate.vue";
import ClientDetails from "@/pages/clients/ClientDetails.vue";
import ClientEdit from "@/pages/clients/ClientEdit.vue";
import ServiceList from "@/pages/services/ServiceList.vue";
import ServiceCreate from "@/pages/services/ServiceCreate.vue";
import ServiceDetails from "@/pages/services/ServiceDetails.vue";
import ServiceEdit from "@/pages/services/ServiceEdit.vue";
import PublicServiceDetails from "@/pages/services/PublicServiceDetails.vue";
import NotFound from "@/pages/NotFound.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "home",
      component: Home,
      meta: { requiresAuth: true, title: "Dashboard" },
    },
    {
      path: "/marketplace",
      name: "marketplace",
      component: Marketplace,
      meta: { public: true, title: "Service marketplace" },
    },
    {
      path: "/marketplace/services/:id",
      name: "public-service-details",
      component: PublicServiceDetails,
      meta: { public: true, title: "Service details" },
    },
    {
      path: "/client/marketplace",
      name: "client-marketplace",
      component: Marketplace,
      meta: {
        requiresAuth: true,
        requiresClient: true,
        title: "Service marketplace",
      },
    },
    {
      path: "/login",
      name: "login",
      component: Login,
      meta: { guestOnly: true, title: "Sign in" },
    },
    {
      path: "/register",
      name: "register",
      component: Register,
      meta: { guestOnly: true, title: "Register" },
    },
    {
      path: "/appointments",
      name: "admin-appointments",
      component: AdminAppointments,
      meta: { requiresAuth: true, requiresAdmin: true, title: "Appointments" },
    },
    {
      path: "/appointments/create",
      name: "appointment-create",
      component: AppointmentCreate,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        title: "Create appointment",
      },
    },
    {
      path: "/appointments/:id",
      name: "appointment-details",
      component: AppointmentDetails,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        title: "Appointment details",
      },
    },
    {
      path: "/appointments/:id/edit",
      name: "appointment-edit",
      component: AppointmentEdit,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        title: "Edit appointment",
      },
    },
    {
      path: "/clients",
      name: "admin-clients",
      component: ClientList,
      meta: { requiresAuth: true, requiresAdmin: true, title: "Clients" },
    },
    {
      path: "/clients/create",
      name: "client-create",
      component: ClientCreate,
      meta: { requiresAuth: true, requiresAdmin: true, title: "Add client" },
    },
    {
      path: "/clients/:id",
      name: "client-details",
      component: ClientDetails,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        title: "Client details",
      },
    },
    {
      path: "/clients/:id/edit",
      name: "client-edit",
      component: ClientEdit,
      meta: { requiresAuth: true, requiresAdmin: true, title: "Edit client" },
    },
    {
      path: "/services",
      name: "admin-services",
      component: ServiceList,
      meta: { requiresAuth: true, requiresAdmin: true, title: "Services" },
    },
    {
      path: "/services/create",
      name: "service-create",
      component: ServiceCreate,
      meta: { requiresAuth: true, requiresAdmin: true, title: "Add service" },
    },
    {
      path: "/services/:id",
      name: "service-details",
      component: ServiceDetails,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        title: "Service details",
      },
    },
    {
      path: "/services/:id/edit",
      name: "service-edit",
      component: ServiceEdit,
      meta: { requiresAuth: true, requiresAdmin: true, title: "Edit service" },
    },
    {
      path: "/client/services/:id",
      name: "client-service-details",
      component: BookAppointment,
      meta: {
        requiresAuth: true,
        requiresClient: true,
        title: "Service details",
      },
    },
    {
      path: "/book",
      name: "client-book",
      redirect: { name: "client-marketplace" },
      meta: { requiresAuth: true, requiresClient: true },
    },
    {
      path: "/client/appointments",
      name: "client-appointments",
      component: ClientAppointments,
      meta: {
        requiresAuth: true,
        requiresClient: true,
        title: "My appointments",
      },
    },
    {
      path: "/:pathMatch(.*)*",
      name: "not-found",
      component: NotFound,
      meta: { title: "Page not found" },
    },
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  if (!auth.isInitialized) await auth.initialize();

  if (to.name === "marketplace" && auth.isAuthenticated)
    return { name: "home" };
  if (to.name === "home" && !auth.isAuthenticated)
    return { name: "marketplace" };

  if (to.meta.guestOnly && auth.isAuthenticated) return { name: "home" };
  if (to.meta.requiresAuth && !auth.isAuthenticated)
    return { path: "/login", query: { redirect: to.fullPath } };
  if (to.meta.requiresAdmin && !auth.isAdmin) return { name: "home" };
  if (to.meta.requiresClient && !auth.isClient) return { name: "home" };
});

router.afterEach((to) => {
  document.title = to.meta.title
    ? `${to.meta.title} · Appointment Desk`
    : "Appointment Desk";
});

export default router;
