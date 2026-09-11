    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
    const formAuthentication = document.querySelector("#formAuthentication");

    if (formAuthentication) {
    FormValidation.formValidation(formAuthentication, {
    fields: {
    title: {
    validators: {
    notEmpty: {
    message: "لطفا نام شرکت یا طرح را وارد کنید"
}
}
},
    CEO: {
    validators: {
    notEmpty: {
    message: "لطفا نام رابط یا مدیرعامل را وارد کنید"
}
}
},
    phone: {
    validators: {
    notEmpty: {
    message: "لطفا شماره همراه را وارد کنید"
},
    regexp: {
    regexp: /^(\+98|0)?9\d{9}$/,
    message: "شماره همراه معتبر نیست"
}
}
},
    email: {
    validators: {
    notEmpty: {
    message: "لطفا آدرس ایمیل را وارد کنید"
},
    emailAddress: {
    message: "ایمیل معتبر نیست"
}
}
},
    password: {
    validators: {
    notEmpty: {
    message: "لطفا رمز عبور را وارد کنید"
},
    stringLength: {
    min: 8,
    message: "رمز عبور باید حداقل 8 کاراکتر باشد"
}
}
},
    password_confirmation: {
    validators: {
    notEmpty: {
    message: "لطفا تایید رمز عبور را وارد کنید"
},
    identical: {
    compare: function () {
    return formAuthentication.querySelector('[name="password"]').value;
},
    message: "رمز عبور و تایید آن یکسان نیستند"
}
}
},
    terms_accepted: {
    validators: {
    notEmpty: {
    message: "لطفا با شرایط و قوانین موافقت کنید"
}
}
}
},
    plugins: {
    trigger: new FormValidation.plugins.Trigger(),
    bootstrap5: new FormValidation.plugins.Bootstrap5({
    eleValidClass: "",
    rowSelector: ".mb-3"
}),
    submitButton: new FormValidation.plugins.SubmitButton(),
    defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
    autoFocus: new FormValidation.plugins.AutoFocus()
},
    init: (instance) => {
    instance.on("plugins.message.placed", function (e) {
    if (e.element.parentElement.classList.contains("input-group")) {
    e.element.parentElement.insertAdjacentElement("afterend", e.messageElement);
}
});
}
});
}
});
