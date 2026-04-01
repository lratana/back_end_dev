<template>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown" data-toggle="dropdown" href="#" role="button">
            <span :class="locale?.icon"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-md dropdown-menu-right">
            <span class="dropdown-item dropdown-header">{{ $t('lang') }}</span>
            <div class="dropdown-divider"></div>
            <a v-for="locale in locales"
               @click.prevent="switchLocale(locale)"
               :key="locale.name"
               href="#"
               class="dropdown-item"
            >
                <span :class="locale.icon"></span> {{ locale.name }}
            </a>
        </div>
    </li>


</template>

<script setup>
import axios from "axios";
import { useI18n } from "vue-i18n";
import { ref, watch } from "vue";
import { getCurrentLocale, getLocales, setCurrentLocale } from "@/lang/index.js";

const { locale: i18nLocale } = useI18n();

// set initial header (important: watch only triggers on change unless immediate:true)
axios.defaults.headers.common["LOCALE"] = i18nLocale.value;

// watch locale changes
watch(
    () => i18nLocale.value,
    (newValue) => {
        axios.defaults.headers.common["LOCALE"] = newValue;
    },
    { immediate: true }
);

// all locales
const locales = ref(getLocales());

// current locale object (km/en object)
const locale = ref(getCurrentLocale());

function switchLocale(newLocale) {
    // save to localStorage
    setCurrentLocale(newLocale);

    // update local state
    locale.value = newLocale;

    // update vue-i18n locale
    i18nLocale.value = newLocale.prefix;

    // (optional) set header right away, no need to wait watch
    axios.defaults.headers.common["LOCALE"] = newLocale.prefix;
}
</script>

<!--<script setup>-->
<!--    import axios from "axios";-->
<!--    import { useI18n } from "vue-i18n";-->
<!--    import {ref, watch} from "vue";-->
<!--    import {getCurrentLocale, getLocales, setCurrentLocale} from "@/lang/index.js";-->

<!--    const { locale: i18nLocale } = useI18n();-->
<!--    //-->
<!--    watch(i18nLocale, (newValue) => {-->
<!--        axios.defaults.headers.common["LOCALE"] = newValue;-->
<!--    });-->

<!--    // all-->
<!--    const locales = ref(getLocales());-->
<!--    //current-->
<!--    const locale = ref(getCurrentLocale());-->

<!--    function switchLocale(newLocale){-->
<!--        locale.value = setCurrentLocale(newLocale);-->
<!--        locale.value = newLocale;-->
<!--        i18nLocale.value = newLocale.prefix;-->
<!--    }-->
<!--</script>-->
