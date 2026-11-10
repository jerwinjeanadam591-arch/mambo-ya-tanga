(function () {
    "use strict";

    var translations = {
        "Home": "Nyumbani",
        "About": "Kuhusu Sisi",
        "Services": "Huduma",
        "Menu": "Menyu",
        "Pages": "Kurasa",
        "Order Food": "Agiza Chakula",
        "Our Team": "Timu Yetu",
        "Guest Stories": "Maoni ya Wageni",
        "Contact": "Mawasiliano",
        "Good food, warm service and memorable moments in Mafinga": "Chakula kizuri, huduma yenye ukarimu na kumbukumbu nzuri Mafinga",
        "Enjoy freshly prepared Tanzanian meals, refreshing juices, takeaway orders and dependable catering for meetings, families and celebrations in Mafinga, Iringa.": "Furahia vyakula vya Kitanzania vilivyoandaliwa freshi, juisi za kuburudisha, chakula cha kuchukua na huduma ya upishi kwa mikutano, familia na sherehe Mafinga, Iringa.",
        "Order Food Now": "Agiza Chakula Sasa",
        "Experienced Kitchen Team": "Timu Yenye Uzoefu Jikoni",
        "Fresh, Quality Food": "Chakula Freshi na Bora",
        "Easy Online Ordering": "Uagizaji Rahisi Mtandaoni",
        "Reliable Guest Service": "Huduma Bora kwa Wageni",
        "Freshly prepared meals, friendly service and dependable quality for every visit.": "Milo iliyoandaliwa freshi, huduma ya kirafiki na ubora wa kuaminika kila unapotutembelea.",
        "About Us": "Kuhusu Sisi",
        "Welcome to": "Karibu",
        "Food Menu": "Menyu ya Chakula",
        "Customer Favourites from Our Kitchen": "Vyakula Vinavyopendwa kutoka Jikoni Kwetu",
        "Food & Guest Services": "Huduma za Chakula na Wageni",
        "Food, Catering & Guest Services": "Chakula, Upishi na Huduma kwa Wageni",
        "Everything You Need for a Great Visit": "Kila Unachohitaji kwa Ziara Nzuri",
        "Restaurant dining": "Huduma ya Mgahawa",
        "Takeaway orders": "Chakula cha Kuchukua",
        "Fresh juices": "Juisi Freshi",
        "Office and meeting catering": "Upishi wa Ofisi na Mikutano",
        "Celebration catering": "Upishi wa Sherehe",
        "Event planning support": "Msaada wa Mipango ya Matukio",
        "Our Hospitality Team": "Timu Yetu ya Huduma kwa Wageni",
        "Team Members": "Wajumbe wa Timu",
        "What Our Guests Say": "Wageni Wetu Wanasemaje",
        "Testimonial": "Maoni",
        "Opening": "Muda wa Kufungua",
        "Monday - Saturday": "Jumatatu - Jumamosi",
        "Sunday": "Jumapili",
        "Newsletter": "Jarida",
        "Join the Mambo ya Tanga community for menu updates, seasonal offers and event catering news from Mafinga.": "Jiunge na jumuiya ya Mambo ya Tanga kwa taarifa za menyu, ofa za msimu na habari za upishi wa matukio kutoka Mafinga.",
        "Your email": "Barua pepe yako",
        "SignUp": "Jisajili",
        "All Right Reserved.": "Haki zote zimehifadhiwa.",
        "Developed By": "Imetengenezwa na",
        "Cookies": "Vidakuzi",
        "Help": "Msaada",
        "FQAs": "Maswali Yanayoulizwa Mara kwa Mara",
        "Order food, juice & catering": "Agiza chakula, juisi na huduma ya upishi",
        "Pickup, delivery, dine-in or pans for your event.": "Chagua kuchukua, kuletwa, kula hapa au mabakuli kwa tukio lako.",
        "Orders are confirmed by our team. Cash and Mobile Money payments are currently confirmed manually after you place your order.": "Maagizo yanathibitishwa na timu yetu. Malipo ya fedha taslimu na simu yanathibitishwa manually baada ya kuagiza.",
        "Ordering open": "Tunapokea maagizo",
        "in Mafinga": "Mafinga",
        "Pickup & delivery available": "Kuchukua na kuletwa kunapatikana",
        "Browse menu": "Tazama menyu",
        "Policies": "Masharti",
        "Cart": "Kikapu",
        "Call us": "Piga simu",
        "Track order": "Fuatilia agizo",
        "Browse by category": "Tazama kwa kundi",
        "All items": "Vitu vyote",
        "Your order": "Agizo lako",
        "Secure checkout": "Malipo salama",
        "Food subtotal": "Jumla ya chakula",
        "Delivery fee": "Ada ya kuleta",
        "Total": "Jumla",
        "Your name": "Jina lako",
        "Phone number (digits only)": "Namba ya simu (tarakimu pekee)",
        "Email (optional)": "Barua pepe (si lazima)",
        "Choose service": "Chagua huduma",
        "Choose delivery area": "Chagua eneo la kuletwa",
        "Delivery/event address (if needed)": "Anwani ya kuletwa/tukio (ikiwa inahitajika)",
        "Choose payment method": "Chagua njia ya malipo",
        "Notes, allergies or catering requirements": "Maelezo, mzio au mahitaji ya upishi",
        "Place order": "Tuma agizo",
        "Payment is confirmed by staff after your order is received.": "Malipo yanathibitishwa na wahudumu baada ya kupokea agizo lako.",
        "Track your order": "Fuatilia agizo lako",
        "Use the reference and private token shown after checkout.": "Tumia namba ya kumbukumbu na tokeni iliyoonyeshwa baada ya malipo.",
        "Order reference": "Namba ya kumbukumbu ya agizo",
        "Order token": "Tokeni ya agizo",
        "Check order status": "Angalia hali ya agizo",
        "Menu": "Menyu",
        "Catering": "Upishi",
        "Desserts": "Vitafunwa vitamu",
        "Drinks": "Vinywaji",
        "Juice": "Juisi",
        "Meals": "Milo",
        "Pans & Catering": "Mabakuli na Upishi",
        "Services": "Huduma",
        "Snacks": "Vitafunwa",
        "Pickup": "Kuchukua",
        "Delivery": "Kuletwa",
        "Dine-in": "Kula hapa",
        "Cash": "Fedha taslimu",
        "Mobile Money": "Pesa za simu",
        "Card": "Kadi",
        "Loading menu...": "Inapakia menyu...",
        "Select items from the menu.": "Chagua vitu kutoka kwenye menyu.",
        "Add to order": "Ongeza kwenye agizo",
        "remove": "ondoa",
        "Mambo ya Tanga is already running.": "Mambo ya Tanga inaendelea kufanya kazi.",
        "English": "Kiingereza",
        "Swahili": "Kiswahili",
        "Mambo ya Tanga | Restaurant, Catering & Events in Mafinga": "Mambo ya Tanga | Mgahawa, Upishi na Matukio Mafinga",
        "Mambo ya Tanga | Hotel in Mafinga, Iringa": "Mambo ya Tanga | Hoteli Mafinga, Iringa",
        "Order Food | Mambo ya Tanga": "Agiza Chakula | Mambo ya Tanga",
        "Our Team": "Timu Yetu",
        "Our Team | Mambo ya Tanga": "Timu Yetu | Mambo ya Tanga",
        "Ordering Policies | Mambo ya Tanga": "Masharti ya Kuagiza | Mambo ya Tanga"
    };

    var sourceKey = "mamboLanguage";
    var language = localStorage.getItem(sourceKey) || "en";

    function translateNode(node) {
        if (node.nodeType !== Node.TEXT_NODE) return;
        var current = node.nodeValue.trim();
        var source = node.__mamboSource;
        if (source && current !== source && current !== (translations[source] || source)) source = current;
        if (!source) source = current;
        if (!source) return;
        node.__mamboSource = source;
        var value = language === "sw" ? (translations[node.__mamboSource] || node.__mamboSource) : node.__mamboSource;
        if (node.nodeValue !== value) {
            var leading = node.nodeValue.match(/^\s*/)[0];
            var trailing = node.nodeValue.match(/\s*$/)[0];
            node.nodeValue = leading + value + trailing;
        }
    }

    function translateAttributes(root) {
        var elements = [];
        if (root.nodeType === Node.ELEMENT_NODE && root.matches("[placeholder],[aria-label],[title]")) elements.push(root);
        root.querySelectorAll("[placeholder],[aria-label],[title]").forEach(function (element) { elements.push(element); });
        elements.forEach(function (element) {
            ["placeholder", "aria-label", "title"].forEach(function (attribute) {
                if (!element.hasAttribute(attribute)) return;
                var source = element.getAttribute("data-i18n-" + attribute) || element.getAttribute(attribute);
                element.setAttribute("data-i18n-" + attribute, source);
                if (language === "sw") element.setAttribute(attribute, translations[source] || source);
                else element.setAttribute(attribute, source);
            });
        });
    }

    function translate(root) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        var node;
        while ((node = walker.nextNode())) translateNode(node);
        translateAttributes(root);
        document.documentElement.lang = language === "sw" ? "sw-TZ" : "en";
        var titleSource = document.title;
        if (language === "sw" && translations[titleSource]) document.title = translations[titleSource];
        if (language === "en") {
            Object.keys(translations).some(function (key) {
                if (translations[key] === document.title) {
                    document.title = key;
                    return true;
                }
                return false;
            });
        }
        var description = document.querySelector('meta[name="description"]');
        if (description) {
            var descriptionSource = description.getAttribute("data-i18n-content") || description.getAttribute("content");
            description.setAttribute("data-i18n-content", descriptionSource);
            if (language === "sw" && translations[descriptionSource]) description.setAttribute("content", translations[descriptionSource]);
            if (language === "en") description.setAttribute("content", descriptionSource);
        }
        var button = document.querySelector("[data-language-switch]");
        if (button) {
            var label = button.querySelector(".language-switch__label");
            if (label) label.textContent = language === "sw" ? "EN" : "SW";
            button.setAttribute("aria-label", language === "sw" ? "Switch to English" : "Badili kwenda Kiswahili");
            button.title = language === "sw" ? "Switch to English" : "Badili kwenda Kiswahili";
        }
    }

    function addSwitcher() {
        var host = document.querySelector(".navbar .navbar-collapse") || document.querySelector(".order-nav__links");
        if (!host || document.querySelector("[data-language-switch]")) return;
        var button = document.createElement("button");
        button.type = "button";
        button.className = "language-switch btn btn-outline-primary";
        button.setAttribute("data-language-switch", "");
        button.innerHTML = '<i class="fa fa-globe" aria-hidden="true"></i><span class="language-switch__label"></span>';
        button.addEventListener("click", function () {
            language = language === "sw" ? "en" : "sw";
            localStorage.setItem(sourceKey, language);
            translate(document.body);
        });
        host.appendChild(button);
    }

    function init() {
        addSwitcher();
        translate(document.body);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) translate(node);
                    if (node.nodeType === Node.TEXT_NODE) translateNode(node);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
    else init();
    window.MamboI18n = { translate: translate, t: function (text) { return language === "sw" ? (translations[text] || text) : text; } };
}());
