<template>
  <div class="flex flex-col items-center justify-center h-screen">
    <div class="loader loader-white">
      <div class="loader-spined">
        <div class="loader--icon">
          <svg
            class="offset-45deg text-primary-500"
            width="27"
            height="27"
            viewBox="0 0 27 27"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M25.9053 1.75122C25.8037 1.44653 25.5498 1.19263 25.2451 1.09106C23.6201 0.735596 22.3506 0.735596 21.0811 0.735596C15.8506 0.735596 12.7021 3.57935 10.3662 7.2356L5.03418 7.2356C4.22168 7.28638 3.25684 7.84497 2.85059 8.60669L0.362305 13.634C0.311523 13.7864 0.260742 13.9895 0.260742 14.1418C0.260742 14.8528 0.768555 15.3606 1.47949 15.3606H6.70996L5.59277 16.5286C4.9834 17.0872 4.93262 18.1536 5.59277 18.8137L8.18262 21.4036C8.74121 21.9622 9.80762 22.0637 10.4678 21.4036L11.585 20.2864V25.5168C11.6357 26.2278 12.1436 26.7356 12.8545 26.7356C13.0068 26.7356 13.21 26.6848 13.3623 26.634L18.3896 24.1458C19.1514 23.7395 19.71 22.7747 19.71 21.9622V16.6301C23.417 14.2942 26.21 11.1458 26.21 5.91528C26.2607 4.64575 26.2607 3.37622 25.9053 1.75122ZM19.7607 9.26685C18.5928 9.26685 17.7295 8.40356 17.7295 7.2356C17.7295 6.11841 18.5928 5.20435 19.7607 5.20435C20.8779 5.20435 21.792 6.11841 21.792 7.2356C21.792 8.40356 20.8779 9.26685 19.7607 9.26685Z"
              fill="currentColor"
            />
          </svg>
        </div>
      </div>

      <div class="pufs text-primary-500">
        <i class="text-primary-500"></i><i></i><i></i> <i></i><i></i><i></i>
        <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i
        ><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i
        ><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i
        ><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i
        ><i></i><i></i>
      </div>
      <div class="particles text-primary-500">
        <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i
        ><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i
        ><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i
        ><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i> <i></i
        ><i></i><i></i> <i></i><i></i><i></i> <i></i><i></i><i></i>
      </div>
      <MainLogo
        class="
          absolute
          block
          h-auto
          max-w-full
          transform
          -translate-x-1/2 -translate-y-1/2
          w-28
          text-primary-400
          top-1/2
          left-1/2
        "
        alt="Crater Logo"
      />
    </div>
  </div>
</template>

<script setup>
import MainLogo from '@/scripts/components/icons/MainLogo.vue'

const props = defineProps({
  showBgOverlay: {
    default: false,
    type: Boolean,
  },
})

function getCraterLogo() {
  return import.meta.env.DEV ? '/img/crater-logo.png' : '/build/img/crater-logo.png'
}
</script>

<style>
/*-------------------------------------------------------------------------------*/
/*--  Rocket Loader Inspired By David (https://codepen.io/dlepaux/pen/pZmVoP)  --*/
/*-------------------------------------------------------------------------------*/

.offset-45deg {
  transform: rotate(45deg);
}

.loader {
  width: 240px;
  height: 240px;
  position: relative;
  display: block;
  margin: 0 auto;
  transition: all 2s ease-out;
  transform: scale(1);
}
.loader:hover {
  transition: all 1s ease-in;
  transform: scale(1.5);
}

.loader-white .loader--icon {
  color: white;
}
.loader-white .pufs > i:after {
  animation-name: puf-white;
}

.loader-spined {
  top: 0;
  right: 0;
  left: 0;
  bottom: 0;
  z-index: 100;
  position: absolute;
  display: block;
  animation: orbit 3s linear infinite;
}

@-webkit-keyframes orbit {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
@-moz-keyframes orbit {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
@-ms-keyframes orbit {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
@keyframes orbit {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
.loader--icon {
  text-align: center;
  width: 25px;
  height: 25px;
  line-height: 25px;
  margin: 0 auto;
  font-size: 26px;
  color: #0a2639;
}

.pufs {
  top: 0;
  right: 0;
  left: 0;
  bottom: 0;
  display: block;
  position: absolute;
}
.pufs > i {
  display: block;
  top: 0;
  right: 0;
  left: 0;
  bottom: 0;
  position: absolute;
}
.pufs > i:after {
  content: url('data:image/svg+xml; utf8, <svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.6875 0.6875C1.75403 0.6875 0.1875 2.25403 0.1875 4.1875C0.1875 6.12097 1.75403 7.6875 3.6875 7.6875C5.62097 7.6875 7.1875 6.12097 7.1875 4.1875C7.1875 2.25403 5.62097 0.6875 3.6875 0.6875Z" fill="%239EA9C4"/></svg>');
  height: 7px;
  width: 7px;
  position: relative;
  border-radius: 100%;
  display: block;
  margin: 0 auto;
  top: 7px;
  font-size: 9px;
  opacity: 0;
  animation-name: puf;
  animation-iteration-count: infinite;
  animation-timing-function: ease-out;
  animation-duration: 3s;
}
.pufs > i:nth-child(1) {
  transform: rotate(8deg);
}
.pufs > i:nth-child(1):after {
  animation-delay: 0.0666666667s;
  margin-top: -1px;
}
.pufs > i:nth-child(2) {
  transform: rotate(16deg);
}
.pufs > i:nth-child(2):after {
  animation-delay: 0.1333333333s;
  margin-top: 1px;
}
.pufs > i:nth-child(3) {
  transform: rotate(24deg);
}
.pufs > i:nth-child(3):after {
  animation-delay: 0.2s;
  margin-top: -1px;
}
.pufs > i:nth-child(4) {
  transform: rotate(32deg);
}
.pufs > i:nth-child(4):after {
  animation-delay: 0.2666666667s;
  margin-top: 1px;
}
.pufs > i:nth-child(5) {
  transform: rotate(40deg);
}
.pufs > i:nth-child(5):after {
  animation-delay: 0.3333333333s;
  margin-top: -1px;
}
.pufs > i:nth-child(6) {
  transform: rotate(48deg);
}
.pufs > i:nth-child(6):after {
  animation-delay: 0.4s;
  margin-top: 1px;
}
.pufs > i:nth-child(7) {
  transform: rotate(56deg);
}
.pufs > i:nth-child(7):after {
  animation-delay: 0.4666666667s;
  margin-top: -1px;
}
.pufs > i:nth-child(8) {
  transform: rotate(64deg);
}
.pufs > i:nth-child(8):after {
  animation-delay: 0.5333333333s;
  margin-top: 1px;
}
.pufs > i:nth-child(9) {
  transform: rotate(72deg);
}
.pufs > i:nth-child(9):after {
  animation-delay: 0.6s;
  margin-top: -1px;
}
.pufs > i:nth-child(10) {
  transform: rotate(80deg);
}
.pufs > i:nth-child(10):after {
  animation-delay: 0.6666666667s;
  margin-top: 1px;
}
.pufs > i:nth-child(11) {
  transform: rotate(88deg);
}
.pufs > i:nth-child(11):after {
  animation-delay: 0.7333333333s;
  margin-top: -1px;
}
.pufs > i:nth-child(12) {
  transform: rotate(96deg);
}
.pufs > i:nth-child(12):after {
  animation-delay: 0.8s;
  margin-top: 1px;
}
.pufs > i:nth-child(13) {
  transform: rotate(104deg);
}
.pufs > i:nth-child(13):after {
  animation-delay: 0.8666666667s;
  margin-top: -1px;
}
.pufs > i:nth-child(14) {
  transform: rotate(112deg);
}
.pufs > i:nth-child(14):after {
  animation-delay: 0.9333333333s;
  margin-top: 1px;
}
.pufs > i:nth-child(15) {
  transform: rotate(120deg);
}
.pufs > i:nth-child(15):after {
  animation-delay: 1s;
  margin-top: -1px;
}
.pufs > i:nth-child(16) {
  transform: rotate(128deg);
}
.pufs > i:nth-child(16):after {
  animation-delay: 1.0666666667s;
  margin-top: 1px;
}
.pufs > i:nth-child(17) {
  transform: rotate(136deg);
}
.pufs > i:nth-child(17):after {
  animation-delay: 1.1333333333s;
  margin-top: -1px;
}
.pufs > i:nth-child(18) {
  transform: rotate(144deg);
}
.pufs > i:nth-child(18):after {
  animation-delay: 1.2s;
  margin-top: 1px;
}
.pufs > i:nth-child(19) {
  transform: rotate(152deg);
}
.pufs > i:nth-child(19):after {
  animation-delay: 1.2666666667s;
  margin-top: -1px;
}
.pufs > i:nth-child(20) {
  transform: rotate(160deg);
}
.pufs > i:nth-child(20):after {
  animation-delay: 1.3333333333s;
  margin-top: 1px;
}
.pufs > i:nth-child(21) {
  transform: rotate(168deg);
}
.pufs > i:nth-child(21):after {
  animation-delay: 1.4s;
  margin-top: -1px;
}
.pufs > i:nth-child(22) {
  transform: rotate(176deg);
}
.pufs > i:nth-child(22):after {
  animation-delay: 1.4666666667s;
  margin-top: 1px;
}
.pufs > i:nth-child(23) {
  transform: rotate(184deg);
}
.pufs > i:nth-child(23):after {
  animation-delay: 1.5333333333s;
  margin-top: -1px;
}
.pufs > i:nth-child(24) {
  transform: rotate(192deg);
}
.pufs > i:nth-child(24):after {
  animation-delay: 1.6s;
  margin-top: 1px;
}
.pufs > i:nth-child(25) {
  transform: rotate(200deg);
}
.pufs > i:nth-child(25):after {
  animation-delay: 1.6666666667s;
  margin-top: -1px;
}
.pufs > i:nth-child(26) {
  transform: rotate(208deg);
}
.pufs > i:nth-child(26):after {
  animation-delay: 1.7333333333s;
  margin-top: 1px;
}
.pufs > i:nth-child(27) {
  transform: rotate(216deg);
}
.pufs > i:nth-child(27):after {
  animation-delay: 1.8s;
  margin-top: -1px;
}
.pufs > i:nth-child(28) {
  transform: rotate(224deg);
}
.pufs > i:nth-child(28):after {
  animation-delay: 1.8666666667s;
  margin-top: 1px;
}
.pufs > i:nth-child(29) {
  transform: rotate(232deg);
}
.pufs > i:nth-child(29):after {
  animation-delay: 1.9333333333s;
  margin-top: -1px;
}
.pufs > i:nth-child(30) {
  transform: rotate(240deg);
}
.pufs > i:nth-child(30):after {
  animation-delay: 2s;
  margin-top: 1px;
}
.pufs > i:nth-child(31) {
  transform: rotate(248deg);
}
.pufs > i:nth-child(31):after {
  animation-delay: 2.0666666667s;
  margin-top: -1px;
}
.pufs > i:nth-child(32) {
  transform: rotate(256deg);
}
.pufs > i:nth-child(32):after {
  animation-delay: 2.1333333333s;
  margin-top: 1px;
}
.pufs > i:nth-child(33) {
  transform: rotate(264deg);
}
.pufs > i:nth-child(33):after {
  animation-delay: 2.2s;
  margin-top: -1px;
}
.pufs > i:nth-child(34) {
  transform: rotate(272deg);
}
.pufs > i:nth-child(34):after {
  animation-delay: 2.2666666667s;
  margin-top: 1px;
}
.pufs > i:nth-child(35) {
  transform: rotate(280deg);
}
.pufs > i:nth-child(35):after {
  animation-delay: 2.3333333333s;
  margin-top: -1px;
}
.pufs > i:nth-child(36) {
  transform: rotate(288deg);
}
.pufs > i:nth-child(36):after {
  animation-delay: 2.4s;
  margin-top: 1px;
}
.pufs > i:nth-child(37) {
  transform: rotate(296deg);
}
.pufs > i:nth-child(37):after {
  animation-delay: 2.4666666667s;
  margin-top: -1px;
}
.pufs > i:nth-child(38) {
  transform: rotate(304deg);
}
.pufs > i:nth-child(38):after {
  animation-delay: 2.5333333333s;
  margin-top: 1px;
}
.pufs > i:nth-child(39) {
  transform: rotate(312deg);
}
.pufs > i:nth-child(39):after {
  animation-delay: 2.6s;
  margin-top: -1px;
}
.pufs > i:nth-child(40) {
  transform: rotate(320deg);
}
.pufs > i:nth-child(40):after {
  animation-delay: 2.6666666667s;
  margin-top: 1px;
}
.pufs > i:nth-child(41) {
  transform: rotate(328deg);
}
.pufs > i:nth-child(41):after {
  animation-delay: 2.7333333333s;
  margin-top: -1px;
}
.pufs > i:nth-child(42) {
  transform: rotate(336deg);
}
.pufs > i:nth-child(42):after {
  animation-delay: 2.8s;
  margin-top: 1px;
}
.pufs > i:nth-child(43) {
  transform: rotate(344deg);
}
.pufs > i:nth-child(43):after {
  animation-delay: 2.8666666667s;
  margin-top: -1px;
}
.pufs > i:nth-child(44) {
  transform: rotate(352deg);
}
.pufs > i:nth-child(44):after {
  animation-delay: 2.9333333333s;
  margin-top: 1px;
}
.pufs > i:nth-child(45) {
  transform: rotate(360deg);
}
.pufs > i:nth-child(45):after {
  animation-delay: 3s;
  margin-top: -1px;
}

.particles {
  position: absolute;
  display: block;
  top: 0;
  right: 0;
  left: 0;
  bottom: 0;
}
.particles > i {
  display: block;
  top: 0;
  right: 0;
  left: 0;
  bottom: 0;
  position: absolute;
}
.particles > i:after {
  content: url('data:image/svg+xml; utf8, <svg width="3" height="3" viewBox="0 0 3 3" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.1875 0.6875C0.635081 0.6875 0.1875 1.13508 0.1875 1.6875C0.1875 2.23992 0.635081 2.6875 1.1875 2.6875C1.73992 2.6875 2.1875 2.23992 2.1875 1.6875C2.1875 1.13508 1.73992 0.6875 1.1875 0.6875Z" fill="%239EA9C4"/></svg>');
  height: 7px;
  width: 7px;
  position: relative;
  border-radius: 100%;
  display: block;
  margin: 0 auto;
  top: 7px;
  font-size: 2px;
  opacity: 0;
  margin-top: 0;
  animation-iteration-count: infinite;
  animation-timing-function: ease-out;
  animation-duration: 3s;
}
.particles > i:nth-child(1) {
  transform: rotate(8deg);
}
.particles > i:nth-child(1):after {
  animation-delay: 0.0666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(2) {
  transform: rotate(16deg);
}
.particles > i:nth-child(2):after {
  animation-delay: 0.1333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(3) {
  transform: rotate(24deg);
}
.particles > i:nth-child(3):after {
  animation-delay: 0.2s;
  animation-name: particle;
}
.particles > i:nth-child(4) {
  transform: rotate(32deg);
}
.particles > i:nth-child(4):after {
  animation-delay: 0.2666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(5) {
  transform: rotate(40deg);
}
.particles > i:nth-child(5):after {
  animation-delay: 0.3333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(6) {
  transform: rotate(48deg);
}
.particles > i:nth-child(6):after {
  animation-delay: 0.4s;
  animation-name: particle;
}
.particles > i:nth-child(7) {
  transform: rotate(56deg);
}
.particles > i:nth-child(7):after {
  animation-delay: 0.4666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(8) {
  transform: rotate(64deg);
}
.particles > i:nth-child(8):after {
  animation-delay: 0.5333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(9) {
  transform: rotate(72deg);
}
.particles > i:nth-child(9):after {
  animation-delay: 0.6s;
  animation-name: particle;
}
.particles > i:nth-child(10) {
  transform: rotate(80deg);
}
.particles > i:nth-child(10):after {
  animation-delay: 0.6666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(11) {
  transform: rotate(88deg);
}
.particles > i:nth-child(11):after {
  animation-delay: 0.7333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(12) {
  transform: rotate(96deg);
}
.particles > i:nth-child(12):after {
  animation-delay: 0.8s;
  animation-name: particle;
}
.particles > i:nth-child(13) {
  transform: rotate(104deg);
}
.particles > i:nth-child(13):after {
  animation-delay: 0.8666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(14) {
  transform: rotate(112deg);
}
.particles > i:nth-child(14):after {
  animation-delay: 0.9333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(15) {
  transform: rotate(120deg);
}
.particles > i:nth-child(15):after {
  animation-delay: 1s;
  animation-name: particle;
}
.particles > i:nth-child(16) {
  transform: rotate(128deg);
}
.particles > i:nth-child(16):after {
  animation-delay: 1.0666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(17) {
  transform: rotate(136deg);
}
.particles > i:nth-child(17):after {
  animation-delay: 1.1333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(18) {
  transform: rotate(144deg);
}
.particles > i:nth-child(18):after {
  animation-delay: 1.2s;
  animation-name: particle;
}
.particles > i:nth-child(19) {
  transform: rotate(152deg);
}
.particles > i:nth-child(19):after {
  animation-delay: 1.2666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(20) {
  transform: rotate(160deg);
}
.particles > i:nth-child(20):after {
  animation-delay: 1.3333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(21) {
  transform: rotate(168deg);
}
.particles > i:nth-child(21):after {
  animation-delay: 1.4s;
  animation-name: particle;
}
.particles > i:nth-child(22) {
  transform: rotate(176deg);
}
.particles > i:nth-child(22):after {
  animation-delay: 1.4666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(23) {
  transform: rotate(184deg);
}
.particles > i:nth-child(23):after {
  animation-delay: 1.5333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(24) {
  transform: rotate(192deg);
}
.particles > i:nth-child(24):after {
  animation-delay: 1.6s;
  animation-name: particle;
}
.particles > i:nth-child(25) {
  transform: rotate(200deg);
}
.particles > i:nth-child(25):after {
  animation-delay: 1.6666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(26) {
  transform: rotate(208deg);
}
.particles > i:nth-child(26):after {
  animation-delay: 1.7333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(27) {
  transform: rotate(216deg);
}
.particles > i:nth-child(27):after {
  animation-delay: 1.8s;
  animation-name: particle;
}
.particles > i:nth-child(28) {
  transform: rotate(224deg);
}
.particles > i:nth-child(28):after {
  animation-delay: 1.8666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(29) {
  transform: rotate(232deg);
}
.particles > i:nth-child(29):after {
  animation-delay: 1.9333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(30) {
  transform: rotate(240deg);
}
.particles > i:nth-child(30):after {
  animation-delay: 2s;
  animation-name: particle;
}
.particles > i:nth-child(31) {
  transform: rotate(248deg);
}
.particles > i:nth-child(31):after {
  animation-delay: 2.0666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(32) {
  transform: rotate(256deg);
}
.particles > i:nth-child(32):after {
  animation-delay: 2.1333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(33) {
  transform: rotate(264deg);
}
.particles > i:nth-child(33):after {
  animation-delay: 2.2s;
  animation-name: particle;
}
.particles > i:nth-child(34) {
  transform: rotate(272deg);
}
.particles > i:nth-child(34):after {
  animation-delay: 2.2666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(35) {
  transform: rotate(280deg);
}
.particles > i:nth-child(35):after {
  animation-delay: 2.3333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(36) {
  transform: rotate(288deg);
}
.particles > i:nth-child(36):after {
  animation-delay: 2.4s;
  animation-name: particle;
}
.particles > i:nth-child(37) {
  transform: rotate(296deg);
}
.particles > i:nth-child(37):after {
  animation-delay: 2.4666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(38) {
  transform: rotate(304deg);
}
.particles > i:nth-child(38):after {
  animation-delay: 2.5333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(39) {
  transform: rotate(312deg);
}
.particles > i:nth-child(39):after {
  animation-delay: 2.6s;
  animation-name: particle;
}
.particles > i:nth-child(40) {
  transform: rotate(320deg);
}
.particles > i:nth-child(40):after {
  animation-delay: 2.6666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(41) {
  transform: rotate(328deg);
}
.particles > i:nth-child(41):after {
  animation-delay: 2.7333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(42) {
  transform: rotate(336deg);
}
.particles > i:nth-child(42):after {
  animation-delay: 2.8s;
  animation-name: particle;
}
.particles > i:nth-child(43) {
  transform: rotate(344deg);
}
.particles > i:nth-child(43):after {
  animation-delay: 2.8666666667s;
  animation-name: particle-o;
}
.particles > i:nth-child(44) {
  transform: rotate(352deg);
}
.particles > i:nth-child(44):after {
  animation-delay: 2.9333333333s;
  animation-name: particle-o;
}
.particles > i:nth-child(45) {
  transform: rotate(360deg);
}
.particles > i:nth-child(45):after {
  animation-delay: 3s;
  animation-name: particle;
}

@-webkit-keyframes puf {
  0% {
    opacity: 1;
    color: black;
    transform: scale(1);
  }
  10% {
    color: #3498db;
    transform: scale(1.5);
  }
  60%,
  100% {
    opacity: 0;
    color: grey;
    transform: scale(0.4);
  }
}
@-moz-keyframes puf {
  0% {
    opacity: 1;
    color: black;
    transform: scale(1);
  }
  10% {
    color: #3498db;
    transform: scale(1.5);
  }
  60%,
  100% {
    opacity: 0;
    color: grey;
    transform: scale(0.4);
  }
}
@-ms-keyframes puf {
  0% {
    opacity: 1;
    color: black;
    transform: scale(1);
  }
  10% {
    color: #3498db;
    transform: scale(1.5);
  }
  60%,
  100% {
    opacity: 0;
    color: grey;
    transform: scale(0.4);
  }
}
@keyframes puf {
  0% {
    opacity: 1;
    color: black;
    transform: scale(1);
  }
  10% {
    color: #3498db;
    transform: scale(1.5);
  }
  60%,
  100% {
    opacity: 0;
    color: grey;
    transform: scale(0.4);
  }
}
@-webkit-keyframes puf-white {
  0% {
    opacity: 1;
    color: rgba(0, 0, 0, 0.75);
    transform: scale(1);
  }
  10% {
    color: rgba(255, 255, 255, 0.9);
    transform: scale(1.5);
  }
  60%,
  100% {
    opacity: 0;
    color: rgba(0, 0, 0, 0.3);
    transform: scale(0.4);
  }
}
@-moz-keyframes puf-white {
  0% {
    opacity: 1;
    color: rgba(0, 0, 0, 0.75);
    transform: scale(1);
  }
  10% {
    color: rgba(255, 255, 255, 0.9);
    transform: scale(1.5);
  }
  60%,
  100% {
    opacity: 0;
    color: rgba(0, 0, 0, 0.3);
    transform: scale(0.4);
  }
}
@-ms-keyframes puf-white {
  0% {
    opacity: 1;
    color: rgba(0, 0, 0, 0.75);
    transform: scale(1);
  }
  10% {
    color: rgba(255, 255, 255, 0.9);
    transform: scale(1.5);
  }
  60%,
  100% {
    opacity: 0;
    color: rgba(0, 0, 0, 0.3);
    transform: scale(0.4);
  }
}
@keyframes puf-white {
  0% {
    opacity: 1;
    color: rgba(0, 0, 0, 0.75);
    transform: scale(1);
  }
  10% {
    color: rgba(255, 255, 255, 0.9);
    transform: scale(1.5);
  }
  60%,
  100% {
    opacity: 0;
    color: rgba(0, 0, 0, 0.3);
    transform: scale(0.4);
  }
}
@-webkit-keyframes particle {
  0% {
    opacity: 1;
    color: white;
    margin-top: 0px;
  }
  10% {
    margin-top: 15px;
  }
  75% {
    opacity: 0.5;
    margin-top: 5px;
  }
  100% {
    opacity: 0;
    margin-top: 0px;
  }
}
@-moz-keyframes particle {
  0% {
    opacity: 1;
    color: white;
    margin-top: 0px;
  }
  10% {
    margin-top: 15px;
  }
  75% {
    opacity: 0.5;
    margin-top: 5px;
  }
  100% {
    opacity: 0;
    margin-top: 0px;
  }
}
@-ms-keyframes particle {
  0% {
    opacity: 1;
    color: white;
    margin-top: 0px;
  }
  10% {
    margin-top: 15px;
  }
  75% {
    opacity: 0.5;
    margin-top: 5px;
  }
  100% {
    opacity: 0;
    margin-top: 0px;
  }
}
@keyframes particle {
  0% {
    opacity: 1;
    color: white;
    margin-top: 0px;
  }
  10% {
    margin-top: 15px;
  }
  75% {
    opacity: 0.5;
    margin-top: 5px;
  }
  100% {
    opacity: 0;
    margin-top: 0px;
  }
}
@-webkit-keyframes particle-o {
  0% {
    opacity: 1;
    color: white;
    margin-top: 0px;
  }
  10% {
    margin-top: -7px;
  }
  75% {
    opacity: 0.5;
    margin-top: 0px;
  }
  100% {
    opacity: 0;
    margin-top: 0px;
  }
}
@-moz-keyframes particle-o {
  0% {
    opacity: 1;
    color: white;
    margin-top: 0px;
  }
  10% {
    margin-top: -7px;
  }
  75% {
    opacity: 0.5;
    margin-top: 0px;
  }
  100% {
    opacity: 0;
    margin-top: 0px;
  }
}
@-ms-keyframes particle-o {
  0% {
    opacity: 1;
    color: white;
    margin-top: 0px;
  }
  10% {
    margin-top: -7px;
  }
  75% {
    opacity: 0.5;
    margin-top: 0px;
  }
  100% {
    opacity: 0;
    margin-top: 0px;
  }
}
@keyframes particle-o {
  0% {
    opacity: 1;
    color: white;
    margin-top: 0px;
  }
  10% {
    margin-top: -7px;
  }
  75% {
    opacity: 0.5;
    margin-top: 0px;
  }
  100% {
    opacity: 0;
    margin-top: 0px;
  }
}
</style>
