const defaultEvents = [
  'Animation.animationCanceled',
  'Animation.animationCreated',
  'Animation.animationStarted',
  'ApplicationCache.applicationCacheStatusUpdated',
  'ApplicationCache.networkStateUpdated',
  'CSS.fontsUpdated',
  'CSS.mediaQueryResultChanged',
  'CSS.styleSheetAdded',
  'CSS.styleSheetChanged',
  'CSS.styleSheetRemoved',
  'Database.addDatabase',
  'Debugger.breakpointResolved',
  'Debugger.paused',
  'Debugger.resumed',
  'Debugger.scriptFailedToParse',
  'Debugger.scriptParsed',
  'DOM.attributeModified',
  'DOM.attributeRemoved',
  'DOM.characterDataModified',
  'DOM.childNodeCountUpdated',
  'DOM.childNodeInserted',
  'DOM.childNodeRemoved',
  'DOM.distributedNodesUpdated',
  'DOM.documentUpdated',
  'DOM.inlineStyleInvalidated',
  'DOM.pseudoElementAdded',
  'DOM.pseudoElementRemoved',
  'DOM.setChildNodes',
  'DOM.shadowRootPopped',
  'DOM.shadowRootPushed',
  'DOMStorage.domStorageItemAdded',
  'DOMStorage.domStorageItemRemoved',
  'DOMStorage.domStorageItemsCleared',
  'DOMStorage.domStorageItemUpdated',
  'Emulation.virtualTimeAdvanced',
  'Emulation.virtualTimeBudgetExpired',
  'Emulation.virtualTimePaused',
  'HeadlessExperimental.needsBeginFramesChanged',
  'HeapProfiler.addHeapSnapshotChunk',
  'HeapProfiler.heapStatsUpdate',
  'HeapProfiler.lastSeenObjectId',
  'HeapProfiler.reportHeapSnapshotProgress',
  'HeapProfiler.resetProfiles',
  'Inspector.detached',
  'Inspector.targetCrashed',
  'Inspector.targetReloadedAfterCrash',
  'LayerTree.layerPainted',
  'LayerTree.layerTreeDidChange',
  'Log.entryAdded',
  'Network.dataReceived',
  'Network.eventSourceMessageReceived',
  'Network.loadingFailed',
  'Network.loadingFinished',
  'Network.requestIntercepted',
  'Network.requestServedFromCache',
  'Network.requestWillBeSent',
  'Network.resourceChangedPriority',
  'Network.responseReceived',
  'Network.webSocketClosed',
  'Network.webSocketCreated',
  'Network.webSocketFrameError',
  'Network.webSocketFrameReceived',
  'Network.webSocketFrameSent',
  'Network.webSocketHandshakeResponseReceived',
  'Network.webSocketWillSendHandshakeRequest',
  'Overlay.inspectNodeRequested',
  'Overlay.nodeHighlightRequested',
  'Overlay.screenshotRequested',
  'Page.domContentEventFired',
  'Page.frameAttached',
  'Page.frameClearedScheduledNavigation',
  'Page.frameDetached',
  'Page.frameNavigated',
  'Page.frameResized',
  'Page.frameScheduledNavigation',
  'Page.frameStartedLoading',
  'Page.frameStoppedLoading',
  'Page.interstitialHidden',
  'Page.interstitialShown',
  'Page.javascriptDialogClosed',
  'Page.javascriptDialogOpening',
  'Page.lifecycleEvent',
  'Page.loadEventFired',
  'Page.screencastFrame',
  'Page.screencastVisibilityChanged',
  'Page.windowOpen',
  'Performance.metrics',
  'Profiler.consoleProfileFinished',
  'Profiler.consoleProfileStarted',
  'Runtime.consoleAPICalled',
  'Runtime.exceptionRevoked',
  'Runtime.exceptionThrown',
  'Runtime.executionContextCreated',
  'Runtime.executionContextDestroyed',
  'Runtime.executionContextsCleared',
  'Runtime.inspectRequested',
  'Security.certificateError',
  'Security.securityStateChanged',
  'ServiceWorker.workerErrorReported',
  'ServiceWorker.workerRegistrationUpdated',
  'ServiceWorker.workerVersionUpdated',
  'Storage.cacheStorageContentUpdated',
  'Storage.cacheStorageListUpdated',
  'Storage.indexedDBContentUpdated',
  'Storage.indexedDBListUpdated',
  'Target.attachedToTarget',
  'Target.detachedFromTarget',
  'Target.receivedMessageFromTarget',
  'Target.targetCreated',
  'Target.targetDestroyed',
  'Target.targetInfoChanged',
  'Tethering.accepted',
  // 'Tracing.bufferUsage',
  // 'Tracing.dataCollected',
  // 'Tracing.tracingComplete'
];

const enableMessages = [
  'Animation.enable',
  'ApplicationCache.enable',
  'DOM.enable',
  'CSS.enable',
  'Database.enable',
  'Debugger.enable',
  'DOMStorage.enable',
  'HeapProfiler.enable',
  'IndexedDB.enable',
  'Inspector.enable',
  'LayerTree.enable',
  'Log.enable',
  'Network.enable',
  'Overlay.enable',
  'Page.enable',
  'Performance.enable',
  'Profiler.enable',
  'Runtime.enable',
  'Security.enable',
  'ServiceWorker.enable',
];

const disableMessages = [
  'Animation.disable',
  'DOM.disable',
  'CSS.disable',
  'Database.disable',
  'Debugger.disable',
  'DOMStorage.disable',
  'HeapProfiler.disable',
  'IndexedDB.disable',
  'Inspector.disable',
  'LayerTree.disable',
  'Log.disable',
  'Network.disable',
  'Overlay.disable',
  'Page.disable',
  'Performance.disable',
  'Profiler.disable',
  'Runtime.disable',
  'Security.disable',
  'ServiceWorker.disable',
];

class EventObserver {
  constructor(cdpSession) {
    this._cdpSession = cdpSession;
    this._events = [];
    this._listeners = {};

    this.getEvents = this.getEvents.bind(this);
    this.start = this.start.bind(this);
    this.stop = this.stop.bind(this);
  }

  getEvents() {
    return this._events;
  }

  async start() {
    await Promise.all(enableMessages.map(message => this._cdpSession.send(message)));

    this._cdpSession.send('Log.startViolationsReport', {
      config: [
        { name: 'longTask', threshold: 30 },
        { name: 'longLayout', threshold: 30 },
        { name: 'blockedEvent', threshold: 30 },
        { name: 'blockedParser', threshold: 30 },
        { name: 'discouragedAPIUse', threshold: -1 },
        { name: 'handler', threshold: 30 },
        { name: 'recurringHandler', threshold: 30 }
      ]
    });


    defaultEvents.forEach(eventName => {
      this._listeners[eventName] = data => this._events.push({ name: eventName, data: data });
      this._cdpSession.on(eventName, this._listeners[eventName]);
    });
  }

  async stop() {
    Object.keys(this._listeners).forEach(key => {
      this._cdpSession.off(key, this._listeners[key]);
      delete this._listeners[key];
    });

    this._cdpSession.send('Log.stopViolationsReport');
    await Promise.all(disableMessages.map(message => this._cdpSession.send(message)));
  }
}

module.exports = class {
  static create(cdpSession) {
    return new EventObserver(cdpSession)
  }
};
