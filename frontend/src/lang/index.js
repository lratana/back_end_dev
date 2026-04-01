import km from "./km.json";
import en from "./en.json";

const locales = [
    {
        name: "Khmer (ខ្មែរ)",
        prefix: "km",
        icon: "fi fi-kh"
    },
    {
        name: "English (EN)",
        prefix: "en",
        icon: "fi fi-gb"
    }
];

// messages for vue-i18n
export function getLocaleMessages() {
    return {
        km,
        en
    };
}

// list for dropdown selector
export function getLocales() {
    return locales;
}

// get saved locale from localStorage
export function getCurrentLocale() {
    try {
        const stored = localStorage.getItem("LANGUAGE");

        if (!stored) {
            return locales[0]; // default Khmer
        }

        const parsed = JSON.parse(stored);

        // ensure locale still exists in config
        const found = locales.find(l => l.prefix === parsed.prefix);
        return found || locales[0];

    } catch (e) {
        return locales[0];
    }
}

// save locale
export function setCurrentLocale(locale) {
    const found = locales.find(l => l.prefix === locale.prefix);

    if (found) {
        localStorage.setItem("LANGUAGE", JSON.stringify(found));
        return found;
    }

    return locales[0];
}
