/*! grapesjs-plugin-forms - 2.0.6 */
!(function (e, t) {
  "object" == typeof exports && "object" == typeof module
    ? (module.exports = t())
    : "function" == typeof define && define.amd
    ? define([], t)
    : "object" == typeof exports
    ? (exports["grapesjs-plugin-forms"] = t())
    : (e["grapesjs-plugin-forms"] = t());
})(
  "undefined" != typeof globalThis
    ? globalThis
    : "undefined" != typeof window
    ? window
    : this,
  () =>
    (() => {
      "use strict";
      var e = {
          d: (t, n) => {
            for (var o in n)
              e.o(n, o) &&
                !e.o(t, o) &&
                Object.defineProperty(t, o, { enumerable: !0, get: n[o] });
          },
          o: (e, t) => Object.prototype.hasOwnProperty.call(e, t),
          r: (e) => {
            "undefined" != typeof Symbol &&
              Symbol.toStringTag &&
              Object.defineProperty(e, Symbol.toStringTag, { value: "Module" }),
              Object.defineProperty(e, "__esModule", { value: !0 });
          },
        },
        t = {};
      e.r(t), e.d(t, { default: () => m });
      var n = "form",
        o = "input",
        a = "textarea",
        i = "select",
        s = "checkbox",
        r = "radio",
        p = "button",
        c = "label",
        l = "option";
      var d =
        (void 0 && (void 0).__assign) ||
        function () {
          return (
            (d =
              Object.assign ||
              function (e) {
                for (var t, n = 1, o = arguments.length; n < o; n++)
                  for (var a in (t = arguments[n]))
                    Object.prototype.hasOwnProperty.call(t, a) && (e[a] = t[a]);
                return e;
              }),
            d.apply(this, arguments)
          );
        };
      var u =
        (void 0 && (void 0).__assign) ||
        function () {
          return (
            (u =
              Object.assign ||
              function (e) {
                for (var t, n = 1, o = arguments.length; n < o; n++)
                  for (var a in (t = arguments[n]))
                    Object.prototype.hasOwnProperty.call(t, a) && (e[a] = t[a]);
                return e;
              }),
            u.apply(this, arguments)
          );
        };
      const m = function (e, t) {
        void 0 === t && (t = {});
        var m = u(
          {
            blocks: [
              "form",
              "input",
              "textarea",
              "select",
              "button",
              "label",
              "checkbox",
              "radio",
            ],
            category: { id: "forms", label: "Forms" },
            block: function () {
              return {};
            },
          },
          t
        );
        !(function (e) {
          var t = e.Components,
            d = { name: "name" },
            u = { name: "placeholder" },
            m = { type: "checkbox", name: "required" },
            h = function (e, t) {
              return { type: l, content: t, attributes: { value: e } };
            },
            v = function (t) {
              e.Commands.isActive("preview") || t.preventDefault();
            };
          t.addType(n, {
            isComponent: function (e) {
              return "FORM" == e.tagName;
            },
            model: {
              defaults: {
                tagName: "form",
                droppable: ":not(form)",
                draggable: ":not(form)",
                attributes: { method: "get" },
                traits: [
                  {
                    type: "select",
                    name: "method",
                    options: [
                      { value: "get", name: "GET" },
                      { value: "post", name: "POST" },
                    ],
                  },
                  { name: "action" },
                ],
              },
            },
            view: {
              events: {
                submit: function (e) {
                  return e.preventDefault();
                },
              },
            },
          }),
            t.addType(o, {
              isComponent: function (e) {
                return "INPUT" == e.tagName;
              },
              model: {
                defaults: {
                  tagName: "input",
                  droppable: !1,
                  highlightable: !1,
                  attributes: { type: "text" },
                  traits: [
                    d,
                    u,
                    {
                      type: "select",
                      name: "type",
                      options: [
                        { value: "text" },
                        { value: "email" },
                        { value: "password" },
                        { value: "number" },
                      ],
                    },
                    m,
                  ],
                },
              },
              extendFnView: ["updateAttributes"],
              view: {
                updateAttributes: function () {
                  this.el.setAttribute("autocomplete", "off");
                },
              },
            }),
            t.addType(a, {
              extend: o,
              isComponent: function (e) {
                return "TEXTAREA" == e.tagName;
              },
              model: {
                defaults: {
                  tagName: "textarea",
                  attributes: {},
                  traits: [d, u, m],
                },
              },
            }),
            t.addType(l, {
              isComponent: function (e) {
                return "OPTION" == e.tagName;
              },
              model: {
                defaults: {
                  tagName: "option",
                  layerable: !1,
                  droppable: !1,
                  draggable: !1,
                  highlightable: !1,
                },
              },
            }),
            t.addType(i, {
              isComponent: function (e) {
                return "SELECT" == e.tagName;
              },
              model: {
                defaults: {
                  tagName: "select",
                  droppable: !1,
                  highlightable: !1,
                  components: [h("opt1", "Option 1"), h("opt2", "Option 2")],
                  traits: [d, { name: "options", type: "select-options" }, m],
                },
              },
              view: { events: { mousedown: v } },
            }),
            t.addType(s, {
              extend: o,
              isComponent: function (e) {
                return "INPUT" == e.tagName && "checkbox" == e.type;
              },
              model: {
                defaults: {
                  copyable: !1,
                  attributes: { type: "checkbox" },
                  traits: [
                    { name: "id" },
                    d,
                    { name: "value" },
                    m,
                    { type: "checkbox", name: "checked" },
                  ],
                },
              },
              view: {
                events: { click: v },
                init: function () {
                  this.listenTo(
                    this.model,
                    "change:attributes:checked",
                    this.handleChecked
                  );
                },
                handleChecked: function () {
                  var e;
                  this.el.checked = !!(null ===
                    (e = this.model.get("attributes")) || void 0 === e
                    ? void 0
                    : e.checked);
                },
              },
            }),
            t.addType(r, {
              extend: s,
              isComponent: function (e) {
                return "INPUT" == e.tagName && "radio" == e.type;
              },
              model: { defaults: { attributes: { type: "radio" } } },
            }),
            t.addType(p, {
              extend: o,
              isComponent: function (e) {
                return "BUTTON" == e.tagName;
              },
              model: {
                defaults: {
                  tagName: "button",
                  attributes: { type: "button" },
                  text: "Send",
                  traits: [
                    { name: "text", changeProp: !0 },
                    {
                      type: "select",
                      name: "type",
                      options: [
                        { value: "button" },
                        { value: "submit" },
                        { value: "reset" },
                      ],
                    },
                  ],
                },
                init: function () {
                  var e = this.components(),
                    t = 1 === e.length && e.models[0],
                    n = (t && t.is("textnode") && t.get("content")) || "",
                    o = n || this.get("text");
                  this.set("text", o),
                    this.on("change:text", this.__onTextChange),
                    o !== n && this.__onTextChange();
                },
                __onTextChange: function () {
                  this.components(this.get("text"));
                },
              },
              view: { events: { click: v } },
            }),
            t.addType(c, {
              extend: "text",
              isComponent: function (e) {
                return "LABEL" == e.tagName;
              },
              model: {
                defaults: {
                  tagName: "label",
                  components: "Label",
                  traits: [{ name: "for" }],
                },
              },
            });
        })(e),
          (function (e) {
            e.TraitManager.addType("select-options", {
              events: { keyup: "onChange" },
              onValueChange: function () {
                for (
                  var e,
                    t = this.model,
                    n = this.target,
                    o = t.get("value").trim().split("\n"),
                    a = [],
                    i = 0;
                  i < o.length;
                  i++
                ) {
                  var s = o[i].split("::");
                  a.push({
                    type: l,
                    components: s[1] || s[0],
                    attributes: { value: s[0] },
                  });
                }
                n.components().reset(a),
                  null === (e = n.view) || void 0 === e || e.render();
              },
              getInputEl: function () {
                if (!this.$input) {
                  for (
                    var e = [], t = this.target.components(), n = 0;
                    n < t.length;
                    n++
                  ) {
                    var o = t.models[n],
                      a = o.get("attributes"),
                      i = (null == a ? void 0 : a.value) || "",
                      s = o.components().models[0],
                      r = (s && s.get("content")) || "";
                    e.push("".concat(i, "::").concat(r));
                  }
                  var p = document.createElement("textarea");
                  (p.value = e.join("\n")), (this.$input = p);
                }
                return this.$input;
              },
            });
          })(e),
          (function (e, t) {
            var l = t,
              u = e.BlockManager,
              m = function (e, n) {
                var o;
                (null === (o = l.blocks) || void 0 === o
                  ? void 0
                  : o.indexOf(e)) >= 0 &&
                  u.add(
                    e,
                    d(
                      d(d({}, n), { category: l.category, select: !0 }),
                      t.block(e)
                    )
                  );
              };
            m(n, {
              label: "Form",
              media:
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 5.5c0-.3-.5-.5-1.3-.5H3.4c-.8 0-1.3.2-1.3.5v3c0 .3.5.5 1.3.5h17.4c.8 0 1.3-.2 1.3-.5v-3zM21 8H3V6h18v2zM22 10.5c0-.3-.5-.5-1.3-.5H3.4c-.8 0-1.3.2-1.3.5v3c0 .3.5.5 1.3.5h17.4c.8 0 1.3-.2 1.3-.5v-3zM21 13H3v-2h18v2z"/><rect width="10" height="3" x="2" y="15" rx=".5"/></svg>',
              content: {
                type: n,
                components: [
                  {
                    components: [{ type: c, components: "Name" }, { type: o }],
                  },
                  {
                    components: [
                      { type: c, components: "Email" },
                      { type: o, attributes: { type: "email" } },
                    ],
                  },
                  {
                    components: [
                      { type: c, components: "Gender" },
                      { type: s, attributes: { value: "M" } },
                      { type: c, components: "M" },
                      { type: s, attributes: { value: "F" } },
                      { type: c, components: "F" },
                    ],
                  },
                  {
                    components: [
                      { type: c, components: "Message" },
                      { type: a },
                    ],
                  },
                  { components: [{ type: p }] },
                ],
              },
            }),
              m(o, {
                label: "Input",
                media:
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 9c0-.6-.5-1-1.3-1H3.4C2.5 8 2 8.4 2 9v6c0 .6.5 1 1.3 1h17.4c.8 0 1.3-.4 1.3-1V9zm-1 6H3V9h18v6z"/><path d="M4 10h1v4H4z"/></svg>',
                content: { type: o },
              }),
              m(a, {
                label: "Textarea",
                media:
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 7.5c0-.9-.5-1.5-1.3-1.5H3.4C2.5 6 2 6.6 2 7.5v9c0 .9.5 1.5 1.3 1.5h17.4c.8 0 1.3-.6 1.3-1.5v-9zM21 17H3V7h18v10z"/><path d="M4 8h1v4H4zM19 7h1v10h-1zM20 8h1v1h-1zM20 15h1v1h-1z"/></svg>',
                content: { type: a },
              }),
              m(i, {
                label: "Select",
                media:
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 9c0-.6-.5-1-1.3-1H3.4C2.5 8 2 8.4 2 9v6c0 .6.5 1 1.3 1h17.4c.8 0 1.3-.4 1.3-1V9zm-1 6H3V9h18v6z"/><path d="M18.5 13l1.5-2h-3zM4 11.5h11v1H4z"/></svg>',
                content: { type: i },
              }),
              m(p, {
                label: "Button",
                media:
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 9c0-.6-.5-1-1.3-1H3.4C2.5 8 2 8.4 2 9v6c0 .6.5 1 1.3 1h17.4c.8 0 1.3-.4 1.3-1V9zm-1 6H3V9h18v6z"/><path d="M4 11.5h16v1H4z"/></svg>',
                content: { type: p },
              }),
              m(c, {
                label: "Label",
                media:
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 11.9c0-.6-.5-.9-1.3-.9H3.4c-.8 0-1.3.3-1.3.9V17c0 .5.5.9 1.3.9h17.4c.8 0 1.3-.4 1.3-.9V12zM21 17H3v-5h18v5z"/><rect width="14" height="5" x="2" y="5" rx=".5"/><path d="M4 13h1v3H4z"/></svg>',
                content: { type: c },
              }),
              m(s, {
                label: "Checkbox",
                media:
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 17l-5-5 1.41-1.42L10 14.17l7.59-7.59L19 8m0-5H5c-1.11 0-2 .89-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5a2 2 0 0 0-2-2z"></path></svg>',
                content: { type: s },
              }),
              m(r, {
                label: "Radio",
                media:
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8m0-18C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2m0 5c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"></path></svg>',
                content: { type: r },
              });
          })(e, m);
      };
      return t;
    })()
);
//# sourceMappingURL=index.js.map
