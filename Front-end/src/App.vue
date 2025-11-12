<script setup>
import HeaderComponent from '@/view/components/HeaderComponent.vue'
import FooterComponent from '@/view/components/FooterComponent.vue'

import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const isScrolled = ref(false);

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50; // adjust scroll threshold
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

const isLoggedIn = ref(!!localStorage.getItem('token'));

// window.addEventListener('storage', () => {
//   isLoggedIn.value = !!localStorage.getItem('token');
// });


</script>

<template >
  <div class="page-container">
    <section>
       <header
            v-if="route.name !== 'Homepage' && isLoggedIn"
            class="customized-header"
            :class="{ 'scrolled': isScrolled }"
          >
            <HeaderComponent v-model:is-logged-in="isLoggedIn" />
      </header>
    </section>

    <section id="content-wrap" :class="{ 'content': route.name !== 'Homepage' && route.name !== 'Auth/Signin' && route.name !== 'Auth/Signup' }">
        <router-view />
    </section>

    <FooterComponent class="footer" />
  </div>

</template>


<style scoped>

.page-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh; /* full viewport height */
}

#content-wrap {
  flex: 1; /* pushes footer to bottom */
}

.footer {
  text-align: center;
}
.content{
  padding-top: 130px !important;
}
.customized-header {
  position: fixed;
  width: 100%;
  z-index: 1000;
  overflow: hidden; /* clip pseudo-element */
  justify-self: center;
}

.customized-header::before {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.36);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  z-index: -1;
  opacity: 0;
  transition: opacity 0.5s ease;
}
.customized-header.scrolled::before {
  opacity: 1;
}


</style>
