function SectionWo () {

    const className = 'SectionWo';
    let self = this;
    let classFrom;
    let woTaskId;

    this.init = function () {
        //self.hideSection();

    };

    this.showSection = function () {
        $('.sectionWo').show();
    };

    this.hideSection = function () {
        $('.sectionWo').hide();
    };

    this.getClassName = function () {
        return className;
    };

    this.setClassFrom = function (_classFrom) {
        classFrom = _classFrom;
    };
}