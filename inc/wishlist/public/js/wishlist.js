(function ($) {

    'use strict';

    const ajaxUrl = woocommerce_params.ajax_url;
    const params = snoopy_wishlist_params;
    const ajaxNonce = params.ajax_nonce;
    const shopName = params.shop_name;
    const addWishlist = params.add_wishlist;
    const removeWishlist = params.remove_wishlist;
    const inWishlist = params.in_wishlist;
    const outWishlist = params.out_wishlist;
    const noWishlist = params.no_wishlist;
    const errorWishlist = params.error_wishlist;
    const $body = $('body');
    const loggedIn = !!$body.hasClass('logged-in');
    const isSnoopyWishlist = !!$body.hasClass('snoopy-wishlist');

    let userData = [];
    let wishlist = [];

    Array.prototype.unique = function () {
        return this.filter((value, index, self) => {
            return self.indexOf(value) === index;
        });
    }

    Array.prototype.equals = function (array) {
        if (!array)
            return false;

        if (this.length !== array.length)
            return false;

        for (let i = 0, l = this.length; i < l; i++) {
            if (!array.includes(this[i]))
                return false;
        }

        return true;
    }

    Array.prototype.remove = function (item) {
        return this.filter(ele => ele !== item);
    };

    const getProductId = product => {
        return product.data('product-id').toString();
    }

    const setAddStyles = button => {
        button.attr('title', removeWishlist);
        button.removeClass('add-to-wishlist').addClass('remove-from-wishlist');
        button.find('.fa-heart').removeClass('far').addClass('fas');
    }

    const setRemoveStyles = button => {
        button.attr('title', addWishlist);
        button.removeClass('remove-from-wishlist').addClass('add-to-wishlist');
        button.find('.fa-heart').removeClass('fas').addClass('far');
    }

    const setLocalWishlist = wishlist => {
        wishlist.unique();
        localStorage.setItem('wc-' + shopName + '-wishlist', JSON.stringify(wishlist))
    };

    const getLocalWishlist = () => {
        return JSON.parse(localStorage.getItem('wc-' + shopName + '-wishlist'));
    };

    const ajaxUpdateUserWishlist = (userId, wishlist) => {
        return $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                action: 'wishlist_update_user_data',
                user_id: userId,
                wishlist: wishlist.join(','),
                _ajax_nonce: ajaxNonce,
            }
        });
    }

    const ajaxFetchUserWishlist = () => {
        return $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                action: 'wishlist_fetch_user_data',
                dataType: 'json',
                _ajax_nonce: ajaxNonce,
            }
        });
    }

    const ajaxGetUserWishlist = wishlist => {
        return $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                action: 'wishlist_get_user_data',
                wishlist: wishlist.join(','),
                _ajax_nonce: ajaxNonce,
            }
        });
    }

    const updateLocalWishlist = wishlist => {
        let wishlistLengthBeforeUpdate = getLocalWishlist().length;

        setLocalWishlist(wishlist);

        if (getLocalWishlist().length > wishlistLengthBeforeUpdate) {
            wishlistNotification(inWishlist);
        }

        if (getLocalWishlist().length < wishlistLengthBeforeUpdate) {
            wishlistNotification(outWishlist);
        }

        if (loggedIn) {
            ajaxUpdateUserWishlist(userData['user_id'], getLocalWishlist()).then(() => {
                setStylesWishlist();
            }).fail(() => {
                wishlistNotification(errorWishlist);
            });
        }
    };

    const getUserWishlist = () => {
        let $wishlistTableBody = $('.wishlist-table-body');

        ajaxGetUserWishlist(getLocalWishlist()).then(data => {
            $wishlistTableBody.empty();
            $wishlistTableBody.append(data);
        }).fail(() => {
            wishlistNotification(errorWishlist);
        });
    };

    const setStylesWishlist = () => {
        $('.wishlist-toggle').each(function () {
            const $this = $(this);

            setRemoveStyles($this);

            if (getLocalWishlist().includes(getProductId($this))) {
                setAddStyles($this);
            }
        });
    };

    const wishlistNotification = notification => {
        let $wishlistNotification = $('.wishlist-notification-layer');
        let $wishlistNotificationContent = $('.wishlist-notification-content');

        $wishlistNotification.addClass('is-visible');
        $wishlistNotificationContent.text(notification);

        setTimeout(() => {
            $wishlistNotification.removeClass('is-visible');
        }, 1200);
    }

    const initWishlist = () => {
        if (getLocalWishlist() !== null) {
            wishlist = getLocalWishlist();
        } else {
            setLocalWishlist(wishlist);
        }

        if (loggedIn) {
            ajaxFetchUserWishlist().then(data => {
                userData = JSON.parse(data);
                wishlist = userData['wishlist'].split(',');
                for (let i = 0; i < wishlist.length; i++) {
                    if (wishlist[i] === '') {
                        wishlist.splice(i, 1);
                        i--;
                    }
                }

                if (!wishlist.equals(getLocalWishlist())) {
                    wishlist = getLocalWishlist();
                    updateLocalWishlist(getLocalWishlist());
                }
            }).fail(() => {
                wishlistNotification(errorWishlist);
            });
        } else {
            setStylesWishlist();
        }

        // localStorage.removeItem('wc-' + shopName + '-wishlist');
        //console.log(getLocalWishlist());
    }

    $(document).on('click', '.add-to-wishlist', function (e) {
        e.preventDefault();
        const $this = $(this);

        wishlist.push(getProductId($this));
        updateLocalWishlist(wishlist);
        setAddStyles($this);
    });

    $(document).on('click', '.remove-from-wishlist', function (e) {
        e.preventDefault();
        const $this = $(this);

        wishlist = wishlist.remove(getProductId($this));
        updateLocalWishlist(wishlist);
        setRemoveStyles($this);

        if (isSnoopyWishlist) {
            getUserWishlist();
        }
    });

    // Wishlist initialisation
    initWishlist();
    if (!loggedIn && isSnoopyWishlist) {
        getUserWishlist();
    }

}(jQuery));