

<li class="{{ Route::is('admin.sms-setting') || Route::is('admin.sms-template') || Route::is('admin.edit-sms-template') || Route::is('admin.twilio-sms-setting') || Route::is('admin.biztech-sms-setting') ? 'active' : '' }}">
    <a href="#!" class="collapsed" data-bs-toggle="collapse" data-bs-target="#menu-item__apps_sms_config"><span
            class="menu-bar__text">
    <span class="crancy-menu-icon crancy-svg-icon__v1">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 7.5L9.94202 9.23943C11.6572 10.2535 12.3428 10.2535 14.058 9.23943L17 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M11 19.5C11 19.5 10.0691 19.4878 9.09883 19.4634C5.95033 19.3843 4.37608 19.3448 3.24496 18.2094C2.11383 17.0739 2.08114 15.5412 2.01577 12.4756C1.99475 11.4899 1.99474 10.5101 2.01576 9.52438C2.08114 6.45885 2.11382 4.92608 3.24495 3.79065C4.37608 2.65521 5.95033 2.61566 9.09882 2.53656C11.0393 2.48781 12.9607 2.48781 14.9012 2.53657C18.0497 2.61568 19.6239 2.65523 20.7551 3.79066C21.8862 4.92609 21.9189 6.45886 21.9842 9.52439C21.9918 9.88124 21.9967 10.4995 21.9988 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18 20.2143V21.5M18 20.2143C16.8432 20.2143 15.8241 19.6461 15.2263 18.7833M18 20.2143C19.1568 20.2143 20.1759 19.6461 20.7737 18.7833M15.2263 18.7833L14.0004 19.5714M15.2263 18.7833C14.8728 18.273 14.6667 17.6597 14.6667 17C14.6667 16.3403 14.8727 15.7271 15.2262 15.2169M20.7737 18.7833L21.9996 19.5714M20.7737 18.7833C21.1272 18.273 21.3333 17.6597 21.3333 17C21.3333 16.3403 21.1273 15.7271 20.7738 15.2169M18 13.7857C19.1569 13.7857 20.1761 14.354 20.7738 15.2169M18 13.7857C16.8431 13.7857 15.8239 14.354 15.2262 15.2169M18 13.7857V12.5M20.7738 15.2169L22 14.4286M15.2262 15.2169L14 14.4286" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>

    </span>
    <span class="menu-bar__name">{{ __('translate.Sms Configuration') }}</span></span> <span
            class="crancy__toggle"></span></a></span>
    <!-- Dropdown Menu -->
    <div
        class="collapse crancy__dropdown {{ Route::is('admin.sms-setting') || Route::is('admin.sms-template') || Route::is('admin.edit-sms-template') || Route::is('admin.twilio-sms-setting') || Route::is('admin.biztech-sms-setting') ? 'show' : '' }}"
        id="menu-item__apps_sms_config" data-bs-parent="#CrancyMenu">
        <ul class="menu-bar__one-dropdown">

            <li><a href="{{ route('admin.sms-setting') }}"><span class="menu-bar__text"><span
                            class="menu-bar__name">{{ __('translate.Sms Setting') }}</span></span></a></li>

            <li><a href="{{ route('admin.twilio-sms-setting') }}"><span class="menu-bar__text"><span
                            class="menu-bar__name">{{ __('translate.Twilio Configuration') }}</span></span></a></li>

            <li><a href="{{ route('admin.biztech-sms-setting') }}"><span class="menu-bar__text"><span
                            class="menu-bar__name">{{ __('translate.Biztech Configuration') }}</span></span></a></li>





            <li><a href="{{ route('admin.sms-template') }}"><span class="menu-bar__text"><span
                            class="menu-bar__name">{{ __('translate.Sms Template') }}</span></span></a></li>


        </ul>
    </div>
</li>
