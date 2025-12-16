@php
    $currentRole = adminAuthInfo()->role ?? 'admin';
@endphp
<aside class="vironeer-sidebar">
    <div class="overlay"></div>
    <div class="vironeer-sidebar-header">
        <a href="{{ route('superadmin.dashboard') }}" class="vironeer-sidebar-logo">
            <img src="{{ asset($settings['website_light_logo']) }}" alt="{{ $settings['website_name'] }}" />
        </a>
    </div>
    <div class="vironeer-sidebar-menu" data-simplebar>
        <div class="vironeer-sidebar-links">
            <div class="vironeer-sidebar-links-cont">
            @if($currentRole === 'report_admin')
                <a href="{{ route('superadmin.reports.index') }}"
                class="vironeer-sidebar-link @if (request()->segment(2) == 'reports') current @endif">
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-flag"></i>{{ __('Reported Files') }}</span>
                        @if ($unviewedFileReports)
                            <span class="counter">{{ $unviewedFileReports }}</span>
                        @endif
                    </p>
                </a>
                <a href="{{ route('superadmin.newreports.index') }}"
                    class="vironeer-sidebar-link @if (request()->segment(2) == 'new-reports') current @endif">
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-list"></i>{{ __('New Reports') }}</span>
                        {{-- Optional: show number of files that have unreviewed reports --}}
                        @if (!empty($unviewedFileReportsGrouped) && $unviewedFileReportsGrouped > 0)
                            <span class="counter">{{ $unviewedFileReportsGrouped }}</span>
                        @endif
                    </p>
                </a>
            @endif
            @if($currentRole === 'admin')
                <a href="{{ route('superadmin.dashboard') }}"
                    class="vironeer-sidebar-link @if (request()->segment(2) == 'dashboard') current @endif">
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-th-large"></i>{{ __('Dashboard') }}</span>
                    </p>
                </a>
                <a href="{{ route('superadmin.users.index') }}"
                    class="vironeer-sidebar-link @if (request()->segment(2) == 'users') current @endif">
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fa fa-users"></i>{{ __('Manage Users') }}</span>
                        @if ($unviewedUsersCount)
                            <span class="counter">{{ $unviewedUsersCount }}</span>
                        @endif
                    </p>
                </a>
                <div class="vironeer-sidebar-link @if (request()->segment(2) == 'uploads') active @endif" data-dropdown>
                    <p
                        class="vironeer-sidebar-link-title {{ $usersUnviewedUsersCount || $guestsUnviewedUsersCount ? 'exclamation' : '' }}">
                        <span class="me-3"><i class="fas fa-upload"></i>{{ __('Manage Uploads') }}</span>
                        @if ($usersUnviewedUsersCount || $guestsUnviewedUsersCount)
                            <span class="counter"><span class="fas fa-exclamation"></span></span>
                        @endif
                        <span class="arrow"><i class="fas fa-chevron-right fa-sm"></i></span>
                    </p>
                    <div class="vironeer-sidebar-link-menu">
                        <a href="{{ route('superadmin.uploads.users.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'users') current @endif">
                            <p class="vironeer-sidebar-link-title">
                                <span class="me-1">{{ __('Users Uploads') }}</span>
                                @if ($usersUnviewedUsersCount)
                                    <span class="counter">{{ formatNumber($usersUnviewedUsersCount) }}</span>
                                @endif
                            </p>
                        </a>
                        <a href="{{ route('superadmin.uploads.users_overview.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'users-overview') current @endif">
                            <p class="vironeer-sidebar-link-title">
                                <span class="me-1">{{ __('Users Overview') }}</span>
                            </p>
                        </a>
                        <a href="{{ route('superadmin.uploads.users_trash.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'users-trash') current @endif">
                            <p class="vironeer-sidebar-link-title">
                                <span class="me-1">{{ __('Users Trash') }}</span>
                            </p>
                        </a>
                        <a href="{{ route('superadmin.uploads.guests.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'guests') current @endif">
                            <p class="vironeer-sidebar-link-title">
                                <span class="me-1">{{ __('Guest Uploads') }}</span>
                                @if ($guestsUnviewedUsersCount)
                                    <span class="counter">{{ formatNumber($guestsUnviewedUsersCount) }}</span>
                                @endif
                            </p>
                        </a>
                    </div>
                </div>
                <a href="{{ route('superadmin.reports.index') }}"
                    class="vironeer-sidebar-link @if (request()->segment(2) == 'reports') current @endif">
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-flag"></i>{{ __('Reported Files') }}</span>
                        @if ($unviewedFileReports)
                            <span class="counter">{{ $unviewedFileReports }}</span>
                        @endif
                    </p>
                </a>

                <a href="{{ route('superadmin.newreports.index') }}"
                    class="vironeer-sidebar-link @if (request()->segment(2) == 'new-reports') current @endif">
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-list"></i>{{ __('New Reports') }}</span>
                        {{-- Optional: show number of files that have unreviewed reports --}}
                        @if (!empty($unviewedFileReportsGrouped) && $unviewedFileReportsGrouped > 0)
                            <span class="counter">{{ $unviewedFileReportsGrouped }}</span>
                        @endif
                    </p>
                </a>

                {{-- NEW: Scheduled deletions --}}
                <a href="{{ route('superadmin.purge.index') }}"
                class="vironeer-sidebar-link @if (request()->segment(2) == 'purge') current @endif">
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-trash-alt"></i>{{ __('Scheduled Deletions') }}</span>
                        @if (!empty($pendingPurgeCount))
                            <span class="counter">{{ $pendingPurgeCount }}</span>
                        @endif
                    </p>
                </a>

                @if (licenseType(2))
                    <a href="{{ route('superadmin.subscriptions.index') }}"
                        class="vironeer-sidebar-link @if (request()->segment(2) == 'subscriptions') current @endif">
                        <p class="vironeer-sidebar-link-title">
                            <span><i class="far fa-gem"></i>{{ __('Subscriptions') }}</span>
                            @if ($unviewedSubscriptions)
                                <span class="counter">{{ $unviewedSubscriptions }}</span>
                            @endif
                        </p>
                    </a>
                    <a href="{{ route('superadmin.transactions.index') }}"
                        class="vironeer-sidebar-link @if (request()->segment(2) == 'transactions') current @endif">
                        <p class="vironeer-sidebar-link-title">
                            <span><i class="fas fa-exchange-alt"></i>{{ __('Transactions') }}</span>
                            @if ($unviewedTransactionsCount)
                                <span class="counter">{{ $unviewedTransactionsCount }}</span>
                            @endif
                        </p>
                    </a>
                    <a href="{{ route('superadmin.plans.index') }}"
                        class="vironeer-sidebar-link @if (request()->segment(2) == 'plans') current @endif">
                        <p class="vironeer-sidebar-link-title">
                            <span><i class="fas fa-tags"></i>{{ __('Pricing Plans') }}</span>
                        </p>
                    </a>
                    <a href="{{ route('superadmin.coupons.index') }}"
                        class="vironeer-sidebar-link @if (request()->segment(2) == 'coupons') current @endif">
                        <p class="vironeer-sidebar-link-title">
                            <span><i class="fas fa-percent"></i>{{ __('Coupon Codes') }}</span>
                        </p>
                    </a>
                @endif
                <a href="{{ route('superadmin.advertisements.index') }}"
                    class="vironeer-sidebar-link @if (request()->segment(2) == 'advertisements') current @endif">
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-ad"></i>{{ __('Advertisements') }}</span>
                    </p>
                </a>
            </div>
            <div class="vironeer-sidebar-links-cont">
                <div class="vironeer-sidebar-link @if (request()->segment(2) == 'navigation') active @endif" data-dropdown>
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-bars"></i>{{ __('Navigation') }}</span>
                        <span class="arrow"><i class="fas fa-chevron-right fa-sm"></i></span>
                    </p>
                    <div class="vironeer-sidebar-link-menu">
                        <a href="{{ route('superadmin.navbarMenu.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'navbarMenu') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Navbar Menu') }}</span></p>
                        </a>
                        <a href="{{ route('superadmin.footerMenu.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'footerMenu') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Footer Menu') }}</span></p>
                        </a>
                    </div>
                </div>
                @if ($settings['website_blog_status'])
                    <div class="vironeer-sidebar-link  @if (request()->segment(2) == 'blog') active @endif" data-dropdown>
                        <p class="vironeer-sidebar-link-title">
                            <span><i class="fas fa-rss"></i>{{ __('Blog') }}</span>
                            <span class="arrow"><i class="fas fa-chevron-right fa-sm"></i></span>
                        </p>
                        <div class="vironeer-sidebar-link-menu">
                            <a href="{{ route('articles.index') }}"
                                class="vironeer-sidebar-link @if (request()->segment(3) == 'articles') current @endif">
                                <p class="vironeer-sidebar-link-title"><span>{{ __('Articles') }}</span></p>
                            </a>
                            <a href="{{ route('categories.index') }}"
                                class="vironeer-sidebar-link @if (request()->segment(3) == 'categories') current @endif">
                                <p class="vironeer-sidebar-link-title"><span>{{ __('Categories') }}</span></p>
                            </a>
                            <a href="{{ route('comments.index') }}"
                                class="vironeer-sidebar-link @if (request()->segment(3) == 'comments') current @endif">
                                <p class="vironeer-sidebar-link-title"><span>{{ __('Comments') }}</span>
                                    @if ($commentsNeedsAction)
                                        <span class="counter">{{ $commentsNeedsAction }}</span>
                                    @endif
                                </p>
                            </a>
                        </div>
                    </div>
                @endif
                <div class="vironeer-sidebar-link @if (request()->segment(2) == 'settings') active @endif" data-dropdown>
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-cog"></i>{{ __('Settings') }}</span>
                        <span class="arrow"><i class="fas fa-chevron-right fa-sm"></i></span>
                    </p>
                    <div class="vironeer-sidebar-link-menu">
                        <a href="{{ route('superadmin.settings.general') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'general') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('General Information') }}</span></p>
                        </a>
                        @if (licenseType(1))
                            <a href="{{ route('superadmin.settings.upload.index') }}"
                                class="vironeer-sidebar-link @if (request()->segment(3) == 'upload') current @endif">
                                <p class="vironeer-sidebar-link-title"><span>{{ __('Upload Settings') }}</span></p>
                            </a>
                        @endif
                        <a href="{{ route('superadmin.settings.storage.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'storage') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Storage Providers') }}</span></p>
                        </a>
                        <a href="{{ route('superadmin.settings.smtp') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'smtp') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('SMTP Deatils') }}</span></p>
                        </a>
                        <a href="{{ route('pages.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'pages') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Manage Pages') }}</span></p>
                        </a>
                        <a href="{{ route('admins.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'admins') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Manage Admins') }}</span></p>
                        </a>
                        <a href="{{ route('superadmin.settings.extensions.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'extensions') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Extensions') }}</span></p>
                        </a>
                        <a href="{{ route('languages.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'languages') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Languages') }}</span></p>
                        </a>
                        <a href="{{ route('superadmin.settings.mailtemplates.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'mailtemplates') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Mail Templates') }}</span></p>
                        </a>
                        <a href="{{ route('seo.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'seo') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('SEO Configurations') }}</span></p>
                        </a>
                        @if (licenseType(2))
                            <a href="{{ route('superadmin.settings.gateways.index') }}"
                                class="vironeer-sidebar-link @if (request()->segment(3) == 'gateways') current @endif">
                                <p class="vironeer-sidebar-link-title"><span>{{ __('Payment Gateways') }}</span></p>
                            </a>
                            <a href="{{ route('superadmin.settings.taxes.index') }}"
                                class="vironeer-sidebar-link @if (request()->segment(3) == 'taxes') current @endif">
                                <p class="vironeer-sidebar-link-title"><span>{{ __('Manage Taxes') }}</span></p>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="vironeer-sidebar-links-cont">
                <div class="vironeer-sidebar-link @if (request()->segment(2) == 'others') active @endif" data-dropdown>
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-layer-group"></i>{{ __('Manage sections') }}</span>
                        <span class="arrow"><i class="fas fa-chevron-right fa-sm"></i></span>
                    </p>
                    <div class="vironeer-sidebar-link-menu">
                        <a href="{{ route('superadmin.slideshow.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'slideshow') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Slide Show') }}</span></p>
                        </a>
                        <a href="{{ route('superadmin.features.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'features') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Home Features') }}</span></p>
                        </a>
                        <a href="{{ route('superadmin.faq.index') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'faq') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Home FAQ') }}</span></p>
                        </a>
                        <a href="{{ route('superadmin.download.page') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'download-page') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Download Page') }}</span></p>
                        </a>
                    </div>
                </div>
                <div class="vironeer-sidebar-link @if (request()->segment(3) == 'popup-notice' || request()->segment(3) == 'custom-css') active @endif" data-dropdown>
                    <p class="vironeer-sidebar-link-title">
                        <span><i class="fas fa-plus-square"></i>{{ __('Extra Features') }}</span>
                        <span class="arrow"><i class="fas fa-chevron-right fa-sm"></i></span>
                    </p>
                    <div class="vironeer-sidebar-link-menu">
                        <a href="{{ route('superadmin.additional.notice') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'popup-notice') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('PopUp Notice') }}</span></p>
                        </a>
                        <a href="{{ route('superadmin.additional.css') }}"
                            class="vironeer-sidebar-link @if (request()->segment(3) == 'custom-css') current @endif">
                            <p class="vironeer-sidebar-link-title"><span>{{ __('Custom CSS') }}</span></p>
                        </a>
                    </div>
                </div>
                <a href="{{ route('superadmin.additional.addons.index') }}"
                    class="vironeer-sidebar-link @if (request()->segment(3) == 'addons') current @endif">
                    <p class="vironeer-sidebar-link-title"><i
                            class="fas fa-puzzle-piece"></i><span>{{ __('Addons Manager') }}</span></p>
                </a>
                <a href="{{ route('superadmin.additional.cache') }}" class="vironeer-link-confirm vironeer-sidebar-link">
                    <p class="vironeer-sidebar-link-title"><i
                            class="far fa-trash-alt"></i><span>{{ __('Clear Cache') }}</span></p>
                </a>
            </div>
            @endif
        </div>
    </div>
</aside>