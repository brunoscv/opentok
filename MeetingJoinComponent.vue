<template>
    <div>

        <meeting-nav-bar-component></meeting-nav-bar-component>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="box-header">
                            <h3 class="box-title"> Title: {{ name }} 
                                <a class="btn btn-danger ml-2" style="float:right;" href="javascript:" v-on:click="disconnect()"><i class="fas fa-video-slash"></i> Disconnect </a>
                                <button class="btn btn-primary ml-2" style="float:right" type="button" v-on:click="toggle()" ><i class="fas fa-expand"></i> Fullscreen</button>
                                <a href="javascript:" class="btn btn-primary ml-2" style="float:right" data-toggle="modal" data-animation="fade" data-target=".modal-rightbar"  v-on:click="showModal()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-align-right align-self-center">
                                        <line x1="21" y1="10" x2="7" y2="10"></line>
                                        <line x1="21" y1="6" x2="3" y2="6"></line>
                                        <line x1="21" y1="14" x2="3" y2="14"></line>
                                        <line x1="21" y1="18" x2="7" y2="18"></line>
                                    </svg> Chat
                                </a>
                            </h3>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <fullscreen ref="fullscreen" @change="fullscreenChange">
                                    <div class="table-wrapper" v-if="!loading">
                                        <div id="session" @error="errorHandler">
                                            <publisher :session="session" @error="errorHandler"></publisher>
                                            <div id="subscribers" v-for="stream in streams" :key="stream.streamId">
                                                <subscriber @error="errorHandler" :stream="stream" :session="session"></subscriber>
                                            </div>
                                        </div>
                                    </div>   
                                </fullscreen>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal modal-rightbar fade" tabindex="-1" role="dialog" aria-labelledby="MetricaRightbar" aria-modal="true" id="modalChat">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0" id="MetricaRightbar">Meeting Chat</h5>
                        <button type="button" class="btn btn-sm btn-soft-pink btn-circle btn-square" data-dismiss="modal" aria-hidden="true"><i class="mdi mdi-close"></i></button>
                    </div>
                    <div class="modal-body">                                 
                        <div v-for="message in history">
                            <div> <strong>{{ message.id == authUser.id ? "You" : message.name }} :</strong></div>
                            <div> {{ message.message }}</div>
                        </div>
                    </div><!--end modal-body-->
                    <div class="modal-footer">
                        <textarea class="form-control" rows="3" type="text" v-model="form.message" v-on:keyup.enter="save"></textarea>
                        <div class="btn-group">
                            <button @click.prevent="save" class="btn btn-primary" type="button">
                                <i class="fa fa-check"></i> Send Message
                            </button>
                        </div>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>

    </div>
</template>

<script>
//import defaultListMixin from "../../../../../Shared/mixins/defaultListMixin";
import MeetingNavBarComponent from "./MeetingNavBarComponent";
import Publisher from './MeetingPublisherComponent';
import Subscriber from './MeetingSubscriberComponent';
import OT from '@opentok/client';
import fullscreen from 'vue-fullscreen'
import Vue from 'vue'
import {mapState} from 'vuex';
Vue.use(fullscreen)

const errorHandler = err => {
  alert(err.message);
};

export default {
    name: "MeetingJoinComponent",
    components: {MeetingNavBarComponent, Publisher, Subscriber},
    //mixins: [defaultListMixin],
    props: {
        session: {
        type: OT.Session,
        required: false
        },
        opts: {
        insertMode: "append"
        }
    },
    data() {
        return {
            routeList: "panel.meeting.list",
            routeCreate: "panel.meeting.list",
            redirectToRoute: "",
            apiKey: null,
            sessionId: null,
            token: null,
            id: this.$route.params.id,
            streams: [],
            session: null,
            loading: true,
            name: null,
            fullscreen: false,
            form: {},
            history: [],
            msg: {
                textContent: null,
                className: null,

            },
        }
    },
    computed: mapState(['authUser']),
    created() {
        
    },


    beforeDestroy() {
        this.session.disconnect();
    },
    methods: {
        save() {
            console.log(this.form);
            let vm = this;
            this.session.signal({
                type: 'msg',
                data: {
                    id: this.authUser.id,
                    name: this.authUser.full_name,
                    message: this.form.message
                }
            }, function signalCallback(error) {
                if (error) {
                    console.error('Error sending signal:', error.name, error.message);
                } else {
                    vm.form.message = "";
                }
            });
        },
        showModal() {
            jQuery('#modalChat').modal('show');
        }, 
        disconnect() {
            
            this.$router.push({name: "panel.meeting.list"})
            .catch(err => {
            })
            .then(() => {
                this.session.disconnect();
                this.$awn.success("Disconnected for Meeting succesfully.");
            });
        },
        toggle () {
            this.$refs['fullscreen'].toggle() // recommended
            // this.fullscreen = !this.fullscreen // deprecated
        },
        fullscreenChange (fullscreen) {
            this.fullscreen = fullscreen
        },
        getDetails() {
            this.$loading(true);

            axios.get('/api/meetings/join/' + this.id)
                .then((response) => {

                    let data = response.data.data;
                     
                    this.sessionId = data.session_id;
                    this.token = data.token;
                    this.apiKey = data.api_key;
                    this.name = data.title;

                    this.session = OT.initSession(this.apiKey, this.sessionId);

                    this.session.on('streamCreated', event => {
                        this.streams.push(event.stream);
                    });
                    this.session.on('streamDestroyed', event => {
                        const idx = this.streams.indexOf(event.stream);
                        if (idx > -1) {
                            this.streams.splice(idx, 1);
                        }
                    });
                    this.session.on("sessionDisconnected", function (event) {
                        // The event is defined by the SessionDisconnectEvent class
                        if (event.reason == "networkDisconnected") {
                            alert("Your network connection terminated.")
                        }
                    });
                    this.session.connect(this.token, err => {
                        if (err) {
                            errorHandler(err);
                        }
                    }); 
                    
                    let vm = this;
                    this.session.on('signal:msg', function signalCallback(event) {
                        vm.history.push(event.data);
                        console.log(event.data);
                    });
                    
                    this.loading = false;

                })
                .catch(error => {
                    let message = '';
                    if (_.has(error, 'response.status') && error.response.status == 422) {
                        
                    } 
                    else if (_.has(error, 'response.data.friendly_message')) {
                        
                        message = error.response.status + ' - ' + error.response.data.friendly_message;
                    } else if (_.has(error, 'response.status')) {
                        message = this.$root.$t('messages.server_error') + ' ' + error.response.status;
                    } else {
                        message = this.$root.$t('messages.application_error') + ' ' + error.message;
                        console.log(error);
                    }
                    this.$awn.alert(message);
                })
                .then(() => {
                    this.$loading(false);
                });
        },
        errorHandler,
    },
    async mounted() {
        await this.getDetails();
    },
}
</script>

<style scoped>
    .OT_subscriber {
        float: left;
        margin: 0.4em
    }
    .OT_publisher {
        float: left;
        margin: 0.4em;
    }
</style>
