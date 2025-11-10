<script setup>
  import { Swiper, SwiperSlide} from 'swiper/vue'
  import { Autoplay , Pagination, Navigation } from 'swiper/modules'
  const modules = [Pagination, Navigation,Autoplay]
</script>


<template>
  <div>
    <p class="fs-4 fst-italic text-center">Features</p>
    <swiper
      :allowTouchMove="false"
      :loop="true"
      :speed="6000"
      :centeredSlides="true"
      :autoplay="{
        delay: 0,
        disableOnInteraction: false,
        pauseOnMouseEnter: false,
      }"
      :slidesPerView="3"
      :spaceBetween="16"
      :navigation="false"
      :modules="modules"
      class="mySwiper"
    >
      <swiper-slide>Slide 1</swiper-slide>
      <swiper-slide>Slide 2</swiper-slide>
      <swiper-slide>Slide 3</swiper-slide>
      <swiper-slide>Slide 4</swiper-slide>
      <swiper-slide>Slide 5</swiper-slide>
      <swiper-slide>Slide 6</swiper-slide>
      <swiper-slide>Slide 7</swiper-slide>
      <swiper-slide>Slide 8</swiper-slide>
      <swiper-slide>Slide 9</swiper-slide>
    </swiper>
  </div>

</template>

<style scoped>

.swiper {
  width: 100%;
  height: 100%;
}

.swiper-slide {
  text-align: center;
  font-size: 18px;
  background: #444;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 40px;
  align-content: center;
}

.swiper-slide {
  display: block;
  width: 100%;
  height: 250px;
  object-fit: cover;
}


</style>
