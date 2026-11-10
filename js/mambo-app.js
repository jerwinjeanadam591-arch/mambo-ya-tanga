(function (window) {
    "use strict";

    var STORAGE_KEY = "mamboYaTangaData";
    var seed = {
        menu: [
            { id: "menu-1", name: "Mambo Breakfast", category: "Breakfast", price: 18000, available: true },
            { id: "menu-2", name: "Iringa Beef Fillet", category: "Main Course", price: 32000, available: true },
            { id: "menu-3", name: "Vegetable Curry", category: "Main Course", price: 22000, available: true },
            { id: "menu-4", name: "Fresh Fruit Platter", category: "Dessert", price: 12000, available: true }
        ],
        messages: [],
        subscribers: []
    };

    function read() {
        try {
            var stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY));
            return stored ? Object.assign({}, seed, stored) : JSON.parse(JSON.stringify(seed));
        } catch (error) {
            console.error("Unable to read hotel data.", error);
            return JSON.parse(JSON.stringify(seed));
        }
    }

    function write(data) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            return data;
        } catch (error) {
            console.error("Unable to save hotel data.", error);
            throw new Error("Your browser could not save this update.");
        }
    }

    function createId(prefix) {
        return prefix + "-" + Date.now() + "-" + Math.floor(Math.random() * 1000);
    }

    function add(collection, item) {
        var data = read();
        data[collection].push(Object.assign({ id: createId(collection.slice(0, -1)) }, item));
        write(data);
        return data[collection][data[collection].length - 1];
    }

    function update(collection, id, changes) {
        var data = read();
        var item = data[collection].find(function (entry) { return entry.id === id; });
        if (!item) throw new Error("The selected record was not found.");
        Object.assign(item, changes);
        write(data);
        return item;
    }

    function remove(collection, id) {
        var data = read();
        data[collection] = data[collection].filter(function (entry) { return entry.id !== id; });
        write(data);
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat("en-TZ", { style: "currency", currency: "TZS", maximumFractionDigits: 0 }).format(Number(value) || 0);
    }

    window.MamboApp = {
        brand: {
            name: "Mambo ya Tanga",
            location: "Mafinga, Iringa, Tanzania",
            phone: "0764 830 663",
            email: "hello@mamboyatanga.co.tz"
        },
        read: read,
        add: add,
        update: update,
        remove: remove,
        formatCurrency: formatCurrency,
        createId: createId,
        reset: function () { write(JSON.parse(JSON.stringify(seed))); }
    };

    function showValidation(input, message) {
        input.setCustomValidity(message || "");
    }

    document.querySelectorAll("input[type='email']").forEach(function (input) {
        input.addEventListener("input", function () {
            input.value = input.value.replace(/\s/g, "").toLowerCase();
            showValidation(input, input.value && !input.validity.valid ? "Enter a valid email address." : "");
        });
    });

    document.querySelectorAll("input[type='tel']").forEach(function (input) {
        input.addEventListener("input", function () {
            input.value = input.value.replace(/[^\d+]/g, "").replace(/(?!^)\+/g, "");
            showValidation(input, input.value && !/^\+?\d{9,15}$/.test(input.value) ? "Use 9 to 15 digits, with an optional leading +." : "");
        });
    });

    document.querySelectorAll("input[maxlength], textarea[maxlength]").forEach(function (input) {
        input.addEventListener("input", function () {
            input.value = input.value.replace(/^\s+/, "");
        });
    });

    document.addEventListener("submit", function (event) {
        var form = event.target;
        var submit = form.querySelector('button[type="submit"]');
        if (!submit) return;
        var isContact = /send/i.test(submit.textContent);
        if (!isContact) return;
        event.preventDefault();
        var name = form.querySelector("#name");
        var email = form.querySelector("#email");
        var message = form.querySelector("#message");
        if (!name || !email || !name.value.trim() || !email.value.trim() || !email.validity.valid || !message || !message.value.trim()) return;
        var feedback = document.createElement("div");
        feedback.className = "form-feedback alert alert-success mt-3";
        feedback.setAttribute("role", "status");
        var payload = {
            name: name.value.trim(),
            email: email.value.trim(),
            subject: (form.querySelector("#subject") || {}).value || "Website enquiry",
            message: message.value.trim()
        };
        fetch("api/index.php?action=message", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        }).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok) throw new Error(body.error || "Unable to save your request.");
                return body;
            });
        }).then(function () {
            form.reset();
            feedback.textContent = "Message sent. Our team will contact you shortly.";
            form.appendChild(feedback);
        }).catch(function (error) {
            feedback.classList.remove("alert-success");
            feedback.classList.add("alert-danger");
            feedback.textContent = error.message;
            form.appendChild(feedback);
        });
    }, true);

    document.querySelectorAll('input[name="newsletter_email"]').forEach(function (input) {
        var button = input.parentElement.querySelector("button");
        if (!button) return;
        button.addEventListener("click", function () {
            if (!input.value.trim() || !input.validity.valid) {
                input.focus();
                return;
            }
            fetch("api/index.php?action=subscribe", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email: input.value.trim() })
            }).then(function (response) {
                if (!response.ok) throw new Error("Unable to subscribe right now.");
                input.value = "";
                button.textContent = "Subscribed";
            }).catch(function (error) { button.textContent = error.message; });
        });
    });
}(window));
