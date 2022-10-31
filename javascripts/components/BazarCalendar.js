/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import '../../../bazar/presentation/javascripts/components/BazarCalendar.js';

let BazarCalendar = Vue.options.components.BazarCalendar.options

// duplicate this method to be able to reuse it after mixins
BazarCalendar.methods.manageClickSuper = BazarCalendar.methods.manageClick;
BazarCalendar.methods.updateEventDataSuper = BazarCalendar.methods.updateEventData;

Vue.component('BazarCalendarNew', {
    mixins: [BazarCalendar],
    methods: {
        prepareEvent: function (entry){
            let entryId = entry.id_fiche;
            let existingEvent = this.getEventById(entryId);
            if (!existingEvent && typeof entry.bf_date_debut_evenement != "undefined") {
                let isIframe = this.isModalDisplay()
                let backgroundColor = (entry.color == undefined || entry.color.length == 0) ? "": entry.color;
                let newEvent = {
                    id: entryId,
                    title: entry.bf_titre,
                    start: entry.bf_date_debut_evenement,
                    end: this.formatEndDate(entry),
                    url: entry.url + (isIframe ? '/iframe'+(entry.url.includes('?') ?'&':'?')+'excludeFields=bf_titre':''),
                    allDay: this.isAllDayDate(entry.bf_date_debut_evenement),
                    className: "bazar-entry"+(this.isModalDisplay() ?  " modalbox":""),
                    backgroundColor: backgroundColor,
                    borderColor: backgroundColor,
                    extendedProps: {
                        icon: (entry.icon == undefined || entry.icon.length == 0) ? "": `<i class="${entry.icon}">&nbsp;</i>`,
                        htmlattributes: ((entry.html_data != undefined) ? entry.html_data : '')+
                        (isIframe ? ' data-iframe="1"':'')+
                        ' data-size="modal-lg"',
                        isIframe: isIframe
                    }
                }
                return newEvent;
            }
            return {};
        },
        manageClick: function(info) {
          if (!this.isNewTabDisplay() && !this.isDirectLinkDisplay() && ['listWeek','listMonth','listYear'].indexOf(info.view.type) > -1){ 
            info.jsEvent.preventDefault(); // don't let the browser navigate
          } else {
            this.manageClickSuper(info)
          }
        },
    }
})