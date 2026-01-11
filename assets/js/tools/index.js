/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/tools/App.js":
/*!**************************!*\
  !*** ./src/tools/App.js ***!
  \**************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_ManageFeaturedVideos__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/ManageFeaturedVideos */ "./src/tools/components/ManageFeaturedVideos.js");
/* harmony import */ var _components_Sidebar__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/Sidebar */ "./src/tools/components/Sidebar.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);
/**
 * Main Bulk Actions App Component
 *
 * @package RSFV
 */






const App = () => {
  const [activeTab, setActiveTab] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('manage');
  const tabs = [{
    id: 'manage',
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Manage Featured Videos', 'rsfv')
  }];
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
    className: "rsfv-tools-app",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      className: "rsfv-tabs",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("nav", {
        className: "rsfv-tabs-nav",
        children: tabs.map(tab => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("button", {
          className: `rsfv-tab-button ${activeTab === tab.id ? 'active' : ''}`,
          onClick: () => setActiveTab(tab.id),
          children: tab.label
        }, tab.id))
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
      className: "rsfv-tab-content",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsxs)("div", {
        className: "rsfv-content-wrapper",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)("div", {
          className: "rsfv-main-content",
          children: activeTab === 'manage' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_ManageFeaturedVideos__WEBPACK_IMPORTED_MODULE_2__["default"], {})
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_components_Sidebar__WEBPACK_IMPORTED_MODULE_3__["default"], {})]
      })
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (App);

/***/ }),

/***/ "./src/tools/components/ManageFeaturedVideos.js":
/*!******************************************************!*\
  !*** ./src/tools/components/ManageFeaturedVideos.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _PostsTable__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./PostsTable */ "./src/tools/components/PostsTable.js");
/* harmony import */ var _Pagination__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Pagination */ "./src/tools/components/Pagination.js");
/* harmony import */ var _PostTypeFilter__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./PostTypeFilter */ "./src/tools/components/PostTypeFilter.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__);
/**
 * Manage Featured Videos Component
 *
 * @package RSFV
 */








const ManageFeaturedVideos = () => {
  const [postType, setPostType] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [posts, setPosts] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [page, setPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(1);
  const [totalPages, setTotalPages] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(1);
  const [totalPosts, setTotalPosts] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const [perPage, setPerPage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(window.rsfvTools?.perPage || 20);
  const [search, setSearch] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [searchInput, setSearchInput] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [searchTimeout, setSearchTimeout] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const postTypes = window.rsfvTools?.postTypes || [];

  // Get current post type label.
  const currentPostTypeLabel = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    const found = postTypes.find(pt => pt.value === postType);
    return found ? found.label : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('posts', 'rsfv');
  }, [postTypes, postType]);

  // Set initial post type.
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (postTypes.length > 0 && !postType) {
      setPostType(postTypes[0].value);
    }
  }, [postTypes, postType]);
  const fetchPosts = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(async () => {
    if (!postType) {
      return;
    }
    setLoading(true);
    try {
      let path = `/rsfv/v1/posts?post_type=${postType}&page=${page}&per_page=${perPage}`;
      if (search) {
        path += `&search=${encodeURIComponent(search)}`;
      }
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path,
        parse: false
      });
      const data = await response.json();
      const total = parseInt(response.headers.get('X-WP-Total'), 10);
      const pages = parseInt(response.headers.get('X-WP-TotalPages'), 10);
      setPosts(data);
      setTotalPosts(total);
      setTotalPages(pages);
    } catch (error) {
      console.error('Error fetching posts:', error);
      setPosts([]);
    } finally {
      setLoading(false);
    }
  }, [postType, page, perPage, search]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    fetchPosts();
  }, [fetchPosts]);
  const handlePostTypeChange = newPostType => {
    setPostType(newPostType);
    setPage(1);
    setSearch('');
    setSearchInput('');
  };
  const handlePageChange = newPage => {
    setPage(newPage);
  };
  const handlePerPageChange = newPerPage => {
    setPerPage(newPerPage);
    setPage(1);
  };
  const handleSearchInputChange = value => {
    setSearchInput(value);

    // Clear any existing timeout.
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }

    // If cleared or 3+ characters, trigger search with debounce.
    if (value === '') {
      setSearch('');
      setPage(1);
    } else if (value.length >= 3) {
      const timeout = setTimeout(() => {
        setSearch(value);
        setPage(1);
      }, 300);
      setSearchTimeout(timeout);
    }
  };
  const handleSearchSubmit = e => {
    e.preventDefault();
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }
    setSearch(searchInput);
    setPage(1);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
    className: "rsfv-manage-videos",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
      className: "rsfv-toolbar",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_PostTypeFilter__WEBPACK_IMPORTED_MODULE_5__["default"], {
        postTypes: postTypes,
        selectedPostType: postType,
        onChange: handlePostTypeChange
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        className: "rsfv-per-page",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("label", {
          htmlFor: "rsfv-per-page",
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Per page:', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("select", {
          id: "rsfv-per-page",
          value: perPage,
          onChange: e => handlePerPageChange(parseInt(e.target.value, 10)),
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("option", {
            value: "10",
            children: "10"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("option", {
            value: "20",
            children: "20"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("option", {
            value: "50",
            children: "50"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("option", {
            value: "100",
            children: "100"
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("form", {
        className: "rsfv-search",
        onSubmit: handleSearchSubmit,
        children: [loading && search && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("span", {
          className: "rsfv-search-spinner spinner is-active"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("input", {
          type: "search",
          className: "rsfv-search-input",
          placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.sprintf)(/* translators: %s: post type name */
          (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Search %s...', 'rsfv'), currentPostTypeLabel),
          value: searchInput,
          onChange: e => handleSearchInputChange(e.target.value)
        })]
      })]
    }), loading ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
      className: "rsfv-loading",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("span", {
        className: "spinner is-active"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("span", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Loading posts...', 'rsfv')
      })]
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_PostsTable__WEBPACK_IMPORTED_MODULE_3__["default"], {
        posts: posts,
        onRefresh: fetchPosts
      }), totalPages > 1 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_Pagination__WEBPACK_IMPORTED_MODULE_4__["default"], {
        currentPage: page,
        totalPages: totalPages,
        totalItems: totalPosts,
        onPageChange: handlePageChange
      })]
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ManageFeaturedVideos);

/***/ }),

/***/ "./src/tools/components/Pagination.js":
/*!********************************************!*\
  !*** ./src/tools/components/Pagination.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Pagination Component
 *
 * @package RSFV
 */



const Pagination = ({
  currentPage,
  totalPages,
  totalItems,
  onPageChange
}) => {
  const handlePrevious = () => {
    if (currentPage > 1) {
      onPageChange(currentPage - 1);
    }
  };
  const handleNext = () => {
    if (currentPage < totalPages) {
      onPageChange(currentPage + 1);
    }
  };
  const handleFirst = () => {
    onPageChange(1);
  };
  const handleLast = () => {
    onPageChange(totalPages);
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
    className: "rsfv-pagination tablenav bottom",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: "tablenav-pages",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("span", {
        className: "displaying-num",
        children: [totalItems, " ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('items', 'rsfv')]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("span", {
        className: "pagination-links",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
          className: "first-page button",
          onClick: handleFirst,
          disabled: currentPage === 1,
          "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('First page', 'rsfv'),
          children: "\xAB"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
          className: "prev-page button",
          onClick: handlePrevious,
          disabled: currentPage === 1,
          "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Previous page', 'rsfv'),
          children: "\u2039"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
          className: "paging-input",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("span", {
            className: "tablenav-paging-text",
            children: [currentPage, " ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('of', 'rsfv'), ' ', /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
              className: "total-pages",
              children: totalPages
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
          className: "next-page button",
          onClick: handleNext,
          disabled: currentPage === totalPages,
          "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Next page', 'rsfv'),
          children: "\u203A"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
          className: "last-page button",
          onClick: handleLast,
          disabled: currentPage === totalPages,
          "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Last page', 'rsfv'),
          children: "\xBB"
        })]
      })]
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Pagination);

/***/ }),

/***/ "./src/tools/components/PostTypeFilter.js":
/*!************************************************!*\
  !*** ./src/tools/components/PostTypeFilter.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Post Type Filter Component
 *
 * @package RSFV
 */



const PostTypeFilter = ({
  postTypes,
  selectedPostType,
  onChange
}) => {
  if (!postTypes || postTypes.length === 0) {
    return null;
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    className: "rsfv-post-type-filter",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("label", {
      htmlFor: "rsfv-post-type-select",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Post Type:', 'rsfv')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("select", {
      id: "rsfv-post-type-select",
      value: selectedPostType,
      onChange: e => onChange(e.target.value),
      children: postTypes.map(type => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("option", {
        value: type.value,
        children: type.label
      }, type.value))
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PostTypeFilter);

/***/ }),

/***/ "./src/tools/components/PostsTable.js":
/*!********************************************!*\
  !*** ./src/tools/components/PostsTable.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _VideoTypeSelect__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./VideoTypeSelect */ "./src/tools/components/VideoTypeSelect.js");
/* harmony import */ var _VideoAction__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./VideoAction */ "./src/tools/components/VideoAction.js");
/* harmony import */ var _VideoPreview__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./VideoPreview */ "./src/tools/components/VideoPreview.js");
/* harmony import */ var _ThumbnailCell__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ThumbnailCell */ "./src/tools/components/ThumbnailCell.js");
/* harmony import */ var _hooks__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../hooks */ "./src/tools/hooks.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__);
/**
 * Posts Table Component
 *
 * @package RSFV
 */









const PostsTable = ({
  posts: initialPosts,
  onRefresh
}) => {
  const [posts, setPosts] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(initialPosts);

  // Get columns from config, allowing extensions to add more.
  const columns = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    const baseColumns = window.rsfvTools?.columns || {};
    return (0,_hooks__WEBPACK_IMPORTED_MODULE_6__.applyFilters)('rsfv_tools_columns', baseColumns);
  }, []);

  // Update posts when initialPosts changes.
  if (initialPosts !== posts && initialPosts.length !== posts.length) {
    setPosts(initialPosts);
  }
  if (!posts || posts.length === 0) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("div", {
      className: "rsfv-no-posts",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("p", {
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No posts found for this post type.', 'rsfv')
      })
    });
  }
  const getVideoStatusBadge = post => {
    if (post.has_video) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("span", {
        className: "rsfv-badge rsfv-badge-success",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Has Video', 'rsfv')
      });
    }
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("span", {
      className: "rsfv-badge rsfv-badge-default",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No Video', 'rsfv')
    });
  };
  const handlePostUpdate = (postId, updates) => {
    setPosts(currentPosts => currentPosts.map(post => post.id === postId ? {
      ...post,
      ...updates
    } : post));

    // Trigger action for extensions to listen to.
    (0,_hooks__WEBPACK_IMPORTED_MODULE_6__.doAction)('rsfv_tools_post_updated', postId, updates);
  };

  /**
   * Render cell content based on column key.
   *
   * @param {string} columnKey Column key.
   * @param {Object} post      Post data.
   * @return {JSX.Element|string} Cell content.
   */
  const renderCellContent = (columnKey, post) => {
    // Allow extensions to override cell content.
    const customContent = (0,_hooks__WEBPACK_IMPORTED_MODULE_6__.applyFilters)('rsfv_tools_cell_content', null, columnKey, post, handlePostUpdate);
    if (customContent !== null) {
      return customContent;
    }

    // Default cell renderers.
    switch (columnKey) {
      case 'thumbnail':
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_ThumbnailCell__WEBPACK_IMPORTED_MODULE_5__["default"], {
          post: post,
          onUpdate: handlePostUpdate
        });
      case 'title':
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("strong", {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("a", {
              href: post.edit_link,
              target: "_blank",
              rel: "noopener noreferrer",
              children: post.title || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('(No title)', 'rsfv')
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
            className: "row-actions",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("span", {
              className: "edit",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("a", {
                href: post.edit_link,
                target: "_blank",
                rel: "noopener noreferrer",
                children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Edit', 'rsfv')
              })
            }), ' | ', /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("span", {
              className: "view",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("a", {
                href: post.permalink,
                target: "_blank",
                rel: "noopener noreferrer",
                children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('View', 'rsfv')
              })
            })]
          })]
        });
      case 'status_type':
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("div", {
          className: "rsfv-status-type",
          children: [getVideoStatusBadge(post), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_VideoTypeSelect__WEBPACK_IMPORTED_MODULE_2__["default"], {
            post: post,
            onUpdate: handlePostUpdate
          })]
        });
      case 'video_action':
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_VideoAction__WEBPACK_IMPORTED_MODULE_3__["default"], {
          post: post,
          onUpdate: handlePostUpdate
        });
      case 'video_preview':
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)(_VideoPreview__WEBPACK_IMPORTED_MODULE_4__["default"], {
          post: post
        });
      default:
        // For unknown columns, check if post has data for it.
        return post[columnKey] || '';
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsxs)("table", {
    className: "rsfv-posts-table wp-list-table widefat fixed striped",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("thead", {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("tr", {
        children: Object.entries(columns).map(([key, column]) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("th", {
          className: column.class || '',
          children: column.label
        }, key))
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("tbody", {
      children: posts.map(post => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("tr", {
        children: Object.entries(columns).map(([key, column]) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_7__.jsx)("td", {
          className: column.class || '',
          children: renderCellContent(key, post)
        }, key))
      }, post.id))
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PostsTable);

/***/ }),

/***/ "./src/tools/components/Sidebar.js":
/*!*****************************************!*\
  !*** ./src/tools/components/Sidebar.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Sidebar Component
 *
 * @package RSFV
 */



const Sidebar = () => {
  const isPro = window.rsfvTools?.isPro || false;
  const upgradeUrl = window.rsfvTools?.upgradeUrl || 'https://developer.developer.developer/plugins/developer-developer-featured-video/';
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    className: "rsfv-sidebar",
    children: [!isPro && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: "rsfv-sidebar-panel rsfv-upgrade-banner",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("h3", {
        className: "rsfv-upgrade-title",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('🚀 Ready to go beyond?', 'rsfv')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("p", {
        className: "rsfv-upgrade-description",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Unlock powerful features like advanced video controls, extended WooCommerce integration, and more!', 'rsfv')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("ul", {
        className: "rsfv-upgrade-features",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('✅ Extended Autoplay on Hover', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('✅ Extended WooCommerce Featured Video', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('✅ Support for more Premium/Custom Themes', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('✅ Requests for Theme Compatibility', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('✅ Priority Support', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('And much more...', 'rsfv')
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("a", {
        href: upgradeUrl,
        className: "button button-primary rsfv-upgrade-button",
        target: "_blank",
        rel: "noopener noreferrer",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Upgrade Now', 'rsfv')
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: "rsfv-sidebar-panel",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("h3", {
        className: "rsfv-sidebar-title",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Quick Tips', 'rsfv')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("ul", {
        className: "rsfv-sidebar-tips",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Click on a thumbnail to set or change the featured image.', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Use the video type dropdown to switch between self-hosted and embed videos.', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Set a poster image for self-hosted videos to display before playback.', 'rsfv')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("li", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Search for posts by title using the search field above.', 'rsfv')
        })]
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: "rsfv-sidebar-panel",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("h3", {
        className: "rsfv-sidebar-title",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Keyboard Shortcuts', 'rsfv')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("ul", {
        className: "rsfv-sidebar-shortcuts",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("li", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("kbd", {
            children: "Enter"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Submit search', 'rsfv')
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("li", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("kbd", {
            children: "Esc"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
            children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Close media modal', 'rsfv')
          })]
        })]
      })]
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Sidebar);

/***/ }),

/***/ "./src/tools/components/ThumbnailCell.js":
/*!***********************************************!*\
  !*** ./src/tools/components/ThumbnailCell.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);
/**
 * Thumbnail Cell Component
 *
 * Handles thumbnail display and set/remove actions on hover.
 *
 * @package RSFV
 */





const ThumbnailCell = ({
  post,
  onUpdate
}) => {
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const hasThumbnail = !!post.thumbnail;
  const openMediaUploader = () => {
    const frame = wp.media({
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Select Featured Image', 'rsfv'),
      button: {
        text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Set featured image', 'rsfv')
      },
      library: {
        type: 'image'
      },
      multiple: false
    });
    frame.on('select', async () => {
      const attachment = frame.state().get('selection').first().toJSON();
      setSaving(true);
      try {
        await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
          path: '/rsfv/v1/posts/update-thumbnail',
          method: 'POST',
          data: {
            post_id: post.id,
            thumbnail_id: attachment.id
          }
        });
        if (onUpdate) {
          onUpdate(post.id, {
            thumbnail: attachment.sizes?.thumbnail?.url || attachment.url
          });
        }
      } catch (error) {
        console.error('Error setting thumbnail:', error);
      } finally {
        setSaving(false);
      }
    });
    frame.open();
  };
  const handleRemoveThumbnail = async e => {
    e.stopPropagation();
    setSaving(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/rsfv/v1/posts/update-thumbnail',
        method: 'POST',
        data: {
          post_id: post.id,
          thumbnail_id: 0
        }
      });
      if (onUpdate) {
        onUpdate(post.id, {
          thumbnail: ''
        });
      }
    } catch (error) {
      console.error('Error removing thumbnail:', error);
    } finally {
      setSaving(false);
    }
  };
  if (saving) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
      className: "rsfv-thumbnail-cell rsfv-thumbnail-saving",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
        className: "spinner is-active"
      })
    });
  }
  if (hasThumbnail) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
      className: "rsfv-thumbnail-cell rsfv-has-thumbnail",
      onClick: openMediaUploader,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("img", {
        src: post.thumbnail,
        alt: post.title,
        className: "rsfv-thumbnail"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
        className: "rsfv-thumbnail-overlay rsfv-thumbnail-remove",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
          className: "rsfv-thumbnail-action",
          onClick: handleRemoveThumbnail,
          title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Remove featured image', 'rsfv'),
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
            className: "dashicons dashicons-trash"
          })
        })
      })]
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
    className: "rsfv-thumbnail-cell rsfv-no-thumbnail",
    onClick: openMediaUploader,
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: "dashicons dashicons-format-image"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
      className: "rsfv-thumbnail-overlay rsfv-thumbnail-add",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
        className: "rsfv-thumbnail-action",
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Set featured image', 'rsfv'),
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
          className: "dashicons dashicons-plus-alt2"
        })
      })
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ThumbnailCell);

/***/ }),

/***/ "./src/tools/components/VideoAction.js":
/*!*********************************************!*\
  !*** ./src/tools/components/VideoAction.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);
/**
 * Video Action Component
 *
 * Handles video upload/embed action for each post.
 *
 * @package RSFV
 */





const VideoAction = ({
  post,
  onUpdate
}) => {
  const [embedUrl, setEmbedUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(post.embed_url || '');
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [savingPoster, setSavingPoster] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const videoSource = post.video_source || '';

  // No video type selected.
  if (!videoSource) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: "rsfv-no-action",
      children: "\u2014"
    });
  }
  const openMediaUploader = () => {
    const frame = wp.media({
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Select or Upload Video', 'rsfv'),
      button: {
        text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Use this video', 'rsfv')
      },
      library: {
        type: 'video'
      },
      multiple: false
    });
    frame.on('select', async () => {
      const attachment = frame.state().get('selection').first().toJSON();
      setSaving(true);
      try {
        await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
          path: '/rsfv/v1/posts/update-video',
          method: 'POST',
          data: {
            post_id: post.id,
            video_source: 'self',
            video_id: attachment.id
          }
        });
        if (onUpdate) {
          onUpdate(post.id, {
            video_source: 'self',
            video_id: attachment.id,
            video_url: attachment.url,
            has_video: true
          });
        }
      } catch (error) {
        console.error('Error saving video:', error);
      } finally {
        setSaving(false);
      }
    });
    frame.open();
  };
  const openPosterUploader = () => {
    const frame = wp.media({
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Select Poster Image', 'rsfv'),
      button: {
        text: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Use this image', 'rsfv')
      },
      library: {
        type: 'image'
      },
      multiple: false
    });
    frame.on('select', async () => {
      const attachment = frame.state().get('selection').first().toJSON();
      setSavingPoster(true);
      try {
        await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
          path: '/rsfv/v1/posts/update-poster',
          method: 'POST',
          data: {
            post_id: post.id,
            poster_id: attachment.id
          }
        });
        if (onUpdate) {
          onUpdate(post.id, {
            poster_id: attachment.id,
            poster_url: attachment.url
          });
        }
      } catch (error) {
        console.error('Error saving poster:', error);
      } finally {
        setSavingPoster(false);
      }
    });
    frame.open();
  };

  /**
   * Validate URL format.
   *
   * @param {string} url URL to validate.
   * @return {boolean} True if valid URL.
   */
  const isValidUrl = url => {
    if (!url) {
      return false;
    }
    try {
      const parsedUrl = new URL(url);
      return ['http:', 'https:'].includes(parsedUrl.protocol);
    } catch (e) {
      return false;
    }
  };
  const handleEmbedSave = async () => {
    // Client-side URL validation.
    if (embedUrl && !isValidUrl(embedUrl)) {
      alert((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please enter a valid URL.', 'rsfv'));
      return;
    }
    setSaving(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/rsfv/v1/posts/update-video',
        method: 'POST',
        data: {
          post_id: post.id,
          video_source: 'embed',
          embed_url: embedUrl
        }
      });
      if (onUpdate) {
        onUpdate(post.id, {
          video_source: 'embed',
          embed_url: embedUrl,
          has_video: !!embedUrl
        });
      }
    } catch (error) {
      console.error('Error saving embed URL:', error);
      alert(error.message || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Error saving embed URL.', 'rsfv'));
    } finally {
      setSaving(false);
    }
  };
  const handleRemoveVideo = async () => {
    if (!confirm((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Are you sure you want to remove the video?', 'rsfv'))) {
      return;
    }
    setSaving(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/rsfv/v1/posts/update-video',
        method: 'POST',
        data: {
          post_id: post.id,
          video_source: 'self',
          video_id: 0
        }
      });
      if (onUpdate) {
        onUpdate(post.id, {
          video_id: 0,
          video_url: '',
          has_video: false
        });
      }
    } catch (error) {
      console.error('Error removing video:', error);
    } finally {
      setSaving(false);
    }
  };
  const handleRemovePoster = async () => {
    if (!confirm((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Are you sure you want to remove the poster?', 'rsfv'))) {
      return;
    }
    setSavingPoster(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/rsfv/v1/posts/update-poster',
        method: 'POST',
        data: {
          post_id: post.id,
          poster_id: 0
        }
      });
      if (onUpdate) {
        onUpdate(post.id, {
          poster_id: 0,
          poster_url: ''
        });
      }
    } catch (error) {
      console.error('Error removing poster:', error);
    } finally {
      setSavingPoster(false);
    }
  };

  // Self-hosted video action.
  if (videoSource === 'self') {
    const hasVideo = !!post.video_id;
    const hasPoster = !!post.poster_id;
    const videoButtonText = hasVideo ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Edit Video', 'rsfv') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Upload Video', 'rsfv');
    const videoButtonClass = hasVideo ? 'button button-small' : 'button button-small button-primary';
    const posterButtonText = hasPoster ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Edit Poster', 'rsfv') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Set Poster', 'rsfv');
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
      className: "rsfv-video-action rsfv-self-action",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "rsfv-action-row",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
          className: videoButtonClass,
          onClick: openMediaUploader,
          disabled: saving || savingPoster,
          children: saving ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Saving...', 'rsfv') : videoButtonText
        }), hasVideo && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
          className: "button button-small button-link-delete button-warning",
          onClick: handleRemoveVideo,
          disabled: saving || savingPoster,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Remove', 'rsfv')
        })]
      }), hasVideo && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
        className: "rsfv-action-row",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
          className: "button button-small",
          onClick: openPosterUploader,
          disabled: saving || savingPoster,
          children: savingPoster ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Saving...', 'rsfv') : posterButtonText
        }), hasPoster && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
          className: "button button-small button-link-delete",
          onClick: handleRemovePoster,
          disabled: saving || savingPoster,
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Remove', 'rsfv')
        })]
      })]
    });
  }

  // Embed video action.
  if (videoSource === 'embed') {
    const urlIsValid = !embedUrl || isValidUrl(embedUrl);
    const hasChanged = embedUrl !== (post.embed_url || '');
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
      className: "rsfv-video-action rsfv-embed-action",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
        type: "url",
        className: `rsfv-embed-input${!urlIsValid ? ' rsfv-invalid-url' : ''}`,
        value: embedUrl,
        onChange: e => setEmbedUrl(e.target.value),
        placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enter video URL...', 'rsfv'),
        disabled: saving
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
        className: "button button-small button-primary",
        onClick: handleEmbedSave,
        disabled: saving || !hasChanged || embedUrl && !urlIsValid,
        children: saving ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Saving...', 'rsfv') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Save', 'rsfv')
      })]
    });
  }
  return null;
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VideoAction);

/***/ }),

/***/ "./src/tools/components/VideoPreview.js":
/*!**********************************************!*\
  !*** ./src/tools/components/VideoPreview.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Video Preview Component
 *
 * Displays video preview for posts with featured videos.
 *
 * @package RSFV
 */



const VideoPreview = ({
  post
}) => {
  const videoSource = post.video_source || '';

  // No video type selected.
  if (!videoSource) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "rsfv-no-video",
      children: "\u2014"
    });
  }

  // Self-hosted video preview.
  if (videoSource === 'self' && post.video_id) {
    const videoUrl = post.video_url || '';
    const posterUrl = post.poster_url || '';
    if (videoUrl) {
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: "rsfv-video-preview",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("video", {
          src: videoUrl,
          poster: posterUrl || undefined,
          controls: true,
          muted: true,
          preload: "metadata"
        })
      });
    }
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "rsfv-no-video",
      children: "\u2014"
    });
  }

  // Embed video preview.
  if (videoSource === 'embed' && post.embed_url) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
      className: "rsfv-video-preview rsfv-embed-preview",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("a", {
        href: post.embed_url,
        target: "_blank",
        rel: "noopener noreferrer",
        className: "rsfv-embed-link",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
          className: "dashicons dashicons-video-alt3"
        }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('View Video', 'rsfv')]
      })
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
    className: "rsfv-no-video",
    children: "\u2014"
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VideoPreview);

/***/ }),

/***/ "./src/tools/components/VideoTypeSelect.js":
/*!*************************************************!*\
  !*** ./src/tools/components/VideoTypeSelect.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);
/**
 * Video Type Select Component
 *
 * Handles video type selection for each post.
 *
 * @package RSFV
 */





const VideoTypeSelect = ({
  post,
  onUpdate
}) => {
  const [saving, setSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const handleSourceChange = async newSource => {
    setSaving(true);
    try {
      await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_2___default()({
        path: '/rsfv/v1/posts/update-source',
        method: 'POST',
        data: {
          post_id: post.id,
          video_source: newSource
        }
      });
      if (onUpdate) {
        onUpdate(post.id, {
          video_source: newSource
        });
      }
    } catch (error) {
      console.error('Error updating video source:', error);
    } finally {
      setSaving(false);
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
    className: "rsfv-video-type-select",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("select", {
      value: post.video_source || '',
      onChange: e => handleSourceChange(e.target.value),
      disabled: saving,
      className: "rsfv-video-source-select",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("option", {
        value: "",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Select Type', 'rsfv')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("option", {
        value: "self",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Self Hosted', 'rsfv')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("option", {
        value: "embed",
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Embed', 'rsfv')
      })]
    }), saving && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: "spinner is-active rsfv-inline-spinner"
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VideoTypeSelect);

/***/ }),

/***/ "./src/tools/hooks.js":
/*!****************************!*\
  !*** ./src/tools/hooks.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   addAction: () => (/* binding */ addAction),
/* harmony export */   addFilter: () => (/* binding */ addFilter),
/* harmony export */   applyFilters: () => (/* binding */ applyFilters),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   doAction: () => (/* binding */ doAction),
/* harmony export */   removeAction: () => (/* binding */ removeAction),
/* harmony export */   removeFilter: () => (/* binding */ removeFilter)
/* harmony export */ });
/**
 * RSFV Tools Hooks System
 *
 * Provides a WordPress-like hooks system for extending the Tools app.
 *
 * @package RSFV
 */

const hooks = {
  filters: {},
  actions: {}
};

/**
 * Add a filter callback.
 *
 * @param {string}   hookName Hook name.
 * @param {Function} callback Callback function.
 * @param {number}   priority Priority (default 10).
 */
const addFilter = (hookName, callback, priority = 10) => {
  if (!hooks.filters[hookName]) {
    hooks.filters[hookName] = [];
  }
  hooks.filters[hookName].push({
    callback,
    priority
  });
  hooks.filters[hookName].sort((a, b) => a.priority - b.priority);
};

/**
 * Apply filters to a value.
 *
 * @param {string} hookName Hook name.
 * @param {*}      value    Value to filter.
 * @param {...*}   args     Additional arguments.
 * @return {*} Filtered value.
 */
const applyFilters = (hookName, value, ...args) => {
  if (!hooks.filters[hookName]) {
    return value;
  }
  return hooks.filters[hookName].reduce((acc, {
    callback
  }) => callback(acc, ...args), value);
};

/**
 * Add an action callback.
 *
 * @param {string}   hookName Hook name.
 * @param {Function} callback Callback function.
 * @param {number}   priority Priority (default 10).
 */
const addAction = (hookName, callback, priority = 10) => {
  if (!hooks.actions[hookName]) {
    hooks.actions[hookName] = [];
  }
  hooks.actions[hookName].push({
    callback,
    priority
  });
  hooks.actions[hookName].sort((a, b) => a.priority - b.priority);
};

/**
 * Execute action callbacks.
 *
 * @param {string} hookName Hook name.
 * @param {...*}   args     Arguments to pass to callbacks.
 */
const doAction = (hookName, ...args) => {
  if (!hooks.actions[hookName]) {
    return;
  }
  hooks.actions[hookName].forEach(({
    callback
  }) => callback(...args));
};

/**
 * Remove a filter callback.
 *
 * @param {string}   hookName Hook name.
 * @param {Function} callback Callback to remove.
 */
const removeFilter = (hookName, callback) => {
  if (!hooks.filters[hookName]) {
    return;
  }
  hooks.filters[hookName] = hooks.filters[hookName].filter(item => item.callback !== callback);
};

/**
 * Remove an action callback.
 *
 * @param {string}   hookName Hook name.
 * @param {Function} callback Callback to remove.
 */
const removeAction = (hookName, callback) => {
  if (!hooks.actions[hookName]) {
    return;
  }
  hooks.actions[hookName] = hooks.actions[hookName].filter(item => item.callback !== callback);
};

// Export as global for external extensions.
window.rsfvToolsHooks = {
  addFilter,
  applyFilters,
  addAction,
  doAction,
  removeFilter,
  removeAction
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  addFilter,
  applyFilters,
  addAction,
  doAction,
  removeFilter,
  removeAction
});

/***/ }),

/***/ "./src/tools/index.js":
/*!****************************!*\
  !*** ./src/tools/index.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _App__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./App */ "./src/tools/App.js");
/* harmony import */ var _style_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./style.css */ "./src/tools/style.css");
/* harmony import */ var _hooks__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./hooks */ "./src/tools/hooks.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);
/**
 * Bulk Actions React App Entry Point
 *
 * @package RSFV
 */





// Import hooks to make them globally available.


const container = document.getElementById('rsfv-tools-app');
if (container) {
  const root = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createRoot)(container);
  root.render(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_App__WEBPACK_IMPORTED_MODULE_1__["default"], {}));
}

/***/ }),

/***/ "./src/tools/style.css":
/*!*****************************!*\
  !*** ./src/tools/style.css ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"index": 0,
/******/ 			"./style-index": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunkreally_simple_featured_video"] = globalThis["webpackChunkreally_simple_featured_video"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["./style-index"], () => (__webpack_require__("./src/tools/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map