<script setup>
import { ref, onMounted, defineProps, defineEmits } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import NavigationComponent from './NavigationComponent.vue';

// ---------------- Props & Emits ----------------
const props = defineProps({ isLoggedIn: Boolean });
const emit = defineEmits(['update:isLoggedIn']);

const token = localStorage.getItem("token");
// ---------------- Refs ----------------
const route = useRoute();
const router = useRouter();
const userName = ref('');
const isOpen = ref(false);

// ---------------- Menu Handlers ----------------
const toggleMenu = () => isOpen.value = !isOpen.value;
const closeMenu = () => isOpen.value = false;

// ---------------- Logout ----------------
const logout = async () => {
  if (!token) return router.push("/");

  try {
    const res = await fetch("http://127.0.0.1:8000/api/logout", {
      method: "POST",
      headers: { "Authorization": `Bearer ${token}`, "Accept": "application/json" },
    });
    const data = await res.json();
    alert(data.message || "Logged out successfully");
  } catch (err) {
    console.error("Logout failed:", err);
  } finally {
    localStorage.removeItem("token");
    emit('update:isLoggedIn', false);
    router.push("/");
  }
};

const logoutAndClose = () => {
  logout();
  closeMenu();
};

// ---------------- Fetch Protected User ----------------
onMounted(async () => {
  if (!router.currentRoute.value.meta.requiresAuth) return;
  if (!token) return router.push("/signin");

  try {
    const res = await fetch("http://127.0.0.1:8000/api/protected-route", {
      headers: { "Authorization": `Bearer ${token}`, "Accept": "application/json" },
    });

    if (!res.ok) throw new Error("Unauthorized");

    const data = await res.json();
    userName.value = data?.user?.name || '';
    console.log("data", data);
  } catch (err) {
    console.error("Failed to fetch user:", err);
    localStorage.removeItem("token");
    router.push("/signin");
  }
});

</script>

<template>
    <nav class="navbar">
      <div class="container">
          <div v-if="route.name === 'Homepage' || route.name === 'Auth/Signin' || route.name === 'Auth/Signup'">
            <router-link @click="closeMenu" class="navbar-brand py-2" to="/"><img src="/image/logo.png" alt="Logo" class="logo"></router-link>
          </div>
          <div v-else>
            <router-link @click="closeMenu" class="navbar-brand py-2" to="dashboard"><img src="/image/logo.png" alt="Logo" class="logo"></router-link>
          </div>
          <button @click="toggleMenu" class="navbar-toggler" type="button" >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div :class="['offcanvas-menu', { show: isOpen }]" @transitionend="onTransitionEnd">

          <div class="offcanvas-header">
            <h5 class="text-black text-capitalize" id="offcanvasNavbarLabel">menu</h5>
            <button type="button" class="btn-close p-2 rounded-circle" @click="toggleMenu" aria-label="Close"></button>
          </div>

          <div class="offcanvas-body">
            <ul class="navbar-nav gap-2 text-center" v-if="route.name === 'Homepage' || route.name === 'Auth/Signin' || route.name === 'Auth/Signup'">
              <li class="nav-item">
                <router-link @click="closeMenu"  class="nav-link link" to="signin"><i class="ri-login-box-line"></i> Sign In</router-link>
              </li>
              <li class="nav-item">
                <router-link @click="closeMenu"  class="nav-link link" to="signup"><i class="ri-login-circle-line"> Sign Up </i></router-link>
              </li>
            </ul>

            <ul class="navbar-nav gap-2 text-center mt-5" v-else>
               <NavigationComponent @link-click="closeMenu"  />
              <li class="nav-item" >
                <a class="btn nav-link"><i class="ri-user-6-line"></i> {{userName}} </a>
              </li>
              <li class="nav-item">
                <a @click.prevent="logoutAndClose" class="btn nav-link link">
                  <i class="ri-login-circle-line"></i> Log out
                </a>
              </li>

            </ul>
          </div>

        </div>
      </div>
    </nav>
</template>

<style scoped>
  .offcanvas-menu {
    padding: 20px;
    position: fixed;
    top: 0;
    right: 0;
    width: 400px;
    height: 100vh;
    background: white;

    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.15);
    transform: translateX(100%);
    opacity: 0;
    transition: transform 0.4s ease, opacity 0.4s ease;
    z-index: 1050;
  }

  .offcanvas-menu.show {
    transform: translateX(0);
    opacity: 1;
  }

  .btn-close{
    transition: 0.45s ease-out !important;
  }
  .btn-close:hover{
    box-shadow: var(--bs-btn-close-focus-shadow);
    opacity: var(--bs-btn-close-focus-opacity);
  }

  .logo{
    width: 100px;
  }
  .navbar-collapse {
      flex-grow: 0 !important;
  }

  .link{
    border-bottom: 0 solid black;
    transition: border-bottom-width 0.6s ease-in-out;
  }

  /* hover a  */
  .link:hover{
    border-bottom-width: 1px;
  }

</style>
