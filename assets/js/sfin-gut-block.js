"use strict";

function _instanceof(left, right) {
    if (right != null && typeof Symbol !== "undefined" && right[Symbol.hasInstance]) {
        return right[Symbol.hasInstance](left);
    } else {
        return left instanceof right;
    }
}

function _typeof(obj) {
    if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
        _typeof = function _typeof(obj) {
            return typeof obj;
        };
    } else {
        _typeof = function _typeof(obj) {
            return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
        };
    }
    return _typeof(obj);
}

function _classCallCheck(instance, Constructor) {
    if (!_instanceof(instance, Constructor)) {
        throw new TypeError("Cannot call a class as a function");
    }
}

function _possibleConstructorReturn(self, call) {
    if (call && (_typeof(call) === "object" || typeof call === "function")) {
        return call;
    }
    return _assertThisInitialized(self);
}

function _getPrototypeOf(o) {
    _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
        return o.__proto__ || Object.getPrototypeOf(o);
    };
    return _getPrototypeOf(o);
}

function _assertThisInitialized(self) {
    if (self === void 0) {
        throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
    }
    return self;
}

function _defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
        var descriptor = props[i];
        descriptor.enumerable = descriptor.enumerable || false;
        descriptor.configurable = true;
        if ("value" in descriptor) descriptor.writable = true;
        Object.defineProperty(target, descriptor.key, descriptor);
    }
}

function _createClass(Constructor, protoProps, staticProps) {
    if (protoProps) _defineProperties(Constructor.prototype, protoProps);
    if (staticProps) _defineProperties(Constructor, staticProps);
    return Constructor;
}

function _inherits(subClass, superClass) {
    if (typeof superClass !== "function" && superClass !== null) {
        throw new TypeError("Super expression must either be null or a function");
    }
    subClass.prototype = Object.create(superClass && superClass.prototype, {
        constructor: {
            value: subClass,
            writable: true,
            configurable: true
        }
    });
    if (superClass) _setPrototypeOf(subClass, superClass);
}

function _setPrototypeOf(o, p) {
    _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
        o.__proto__ = p;
        return o;
    };
    return _setPrototypeOf(o, p);
}

var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
var SelectControl = wp.components.SelectControl;
var Component = wp.element.Component;
var InspectorControls = wp.editor.InspectorControls;
var BlockIcon = wp.blocks.BlockIcon;

var sfinSelectPosts =
    /*#__PURE__*/
    function (_Component) {
        _inherits(sfinSelectPosts, _Component);

        _createClass(sfinSelectPosts, null, [{
            key: "getInitialState",
            value: function getInitialState(selectedPost) {
                return {
                    posts: [],
                    selectedPost: selectedPost,
                    post: {}
                };
            }
        }]);

        function sfinSelectPosts() {
            var _this;

            _classCallCheck(this, sfinSelectPosts);

            _this = _possibleConstructorReturn(this, _getPrototypeOf(sfinSelectPosts).apply(this, arguments));
            _this.state = _this.constructor.getInitialState(_this.props.attributes.selectedPost); // Bind so we can use 'this' inside the method.

            _this.getOptions = _this.getOptions.bind(_assertThisInitialized(_this)); // Load posts.

            _this.getOptions();

            _this.onChangeSelectPost = _this.onChangeSelectPost.bind(_assertThisInitialized(_this));
            return _this;
        }
        /**
         * Loading CPT
         */


        _createClass(sfinSelectPosts, [{
            key: "getOptions",
            value: function getOptions() {
                var _this2 = this;

                return wp.apiFetch({
                    path: '/wp/v2/sfin-content-block/?per_page=100'
                }).then(function (posts) {
                    if (posts && 0 !== _this2.state.selectedPost) {
                        // If we have a selected Post, find that post and add it.
                        var post = posts.find(function (item) {
                            return item.id == _this2.state.selectedPost;
                        });

                        _this2.setState({
                            post: post,
                            posts: posts
                        });
                    } else {
                        _this2.setState({
                            posts: posts
                        });
                    }
                });
            }
        }, {
            key: "onChangeSelectPost",
            value: function onChangeSelectPost(value) {
                // Find the post
                var post = this.state.posts.find(function (item) {
                    return item.id == parseInt(value);
                }); // Set the state

                this.setState({
                    selectedPost: parseInt(value),
                    post: post
                }); // Set the attributes

                this.props.setAttributes({
                    selectedPost: parseInt(value),
                    title: post.title.rendered,
                    content: post.content.rendered,
                    link: post.link
                });
            }
        }, {
            key: "render",
            value: function render() {
                var options = [{
                    value: 0,
                    label: __('Select a Post')
                }];

                var output = __('Loading Posts');

                this.props.className += ' loading';

                if (this.state.posts.length > 0) {
                    var loading = __('We have %d posts. Choose one from the right sidebar.');

                    output = loading.replace('%d', this.state.posts.length);
                    this.state.posts.forEach(function (post) {
                        options.push({
                            value: post.id,
                            label: post.title.rendered
                        });
                    });
                } else {
                    output = __('No posts found. Please create some first.');
                }

                if (this.state.post.hasOwnProperty('content')) {
                    output = React.createElement("p", {
                        dangerouslySetInnerHTML: {
                            __html: this.state.post.content.rendered
                        }
                    });
                    this.props.className += ' has-post';
                } else {
                    this.props.className += ' no-post';
                }

                return [!!this.props.isSelected && React.createElement(InspectorControls, {
                    key: "inspector"
                }, React.createElement(SelectControl, {
                    onChange: this.onChangeSelectPost,
                    value: this.props.attributes.selectedPost,
                    label: __('Select a Post'),
                    options: options
                })), React.createElement("section", null, output)];
            }
        }]);

        return sfinSelectPosts;
    }(Component);

registerBlockType('spotfin-content-blocks/sfin-gut-block', {
    title: __('Content Block'),
    // Block title.
    icon: 'schedule',
    category: 'common',
    keywords: [__('load'), __('Load Block'), __('Load Content')],
    attributes: {
        content: {
            type: 'array',
            source: 'children',
            selector: 'section'
        },
        title: {
            type: 'string',
            selector: 'h2'
        },
        link: {
            type: 'string',
            selector: 'a'
        },
        selectedPost: {
            type: 'number',
            default: 0
        }
    },
    save: function save(props) {
        return React.createElement("section", {
            dangerouslySetInnerHTML: {
                __html: props.attributes.content
            }
        }, props.attributes.content);
    },
    edit: sfinSelectPosts
});