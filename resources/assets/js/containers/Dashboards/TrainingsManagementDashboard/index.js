import React, { Component } from "react";
import { connect } from "react-redux";
import { browserHistory } from "react-router";
import moment from "moment";
import _sortBy from "lodash/sortBy";

import { IconButtonBarColumn } from "Components/Table/BuiltInColumns";
import Dashboard from "Components/Dashboard";
import DashboardGrid from "Components/DashboardGrid";
import WorkoutCard from "Components/cards/WorkoutCard";
import Post from "Components/Post";
import Table from "Components/Table";
import Button from "Components/Button";
import Label from "react-bootstrap/lib/Label";

import { displayModal } from "Actions/modalActions";

const mapStateToProps = state => {
  return {
    workouts: Object.values(state.workout.workouts).sort(
      (a, b) => new Date(b.start) - new Date(a.start)
    ),
    workoutTemplates: state.workout.workoutTemplates
  };
};

const mapDispatchToProps = {
  displayModal
};

class TrainingsManagementDashboard extends Component {
  constructor(props) {
    super(props);

    this.openEditWorkoutModal = this.openEditWorkoutModal.bind(this);
  }
  render() {
    var { posts } = this.props;

    return (
      <Dashboard>
        <Dashboard.Content>
        <Table
          columns={[
            {
              Header:'Treningi',
              columns:[
          
            {
              Header: "Nazwa",
              id: "name",
              accessor: workout =>
                this.props.workoutTemplates[workout.workout_template_id].name
            },
            { Header: "Rozpoczyna się", accessor: "start" },
            { Header: "Kończy się", accessor: "end" },
            {
              Header: "Status",
              id:'status',
              accessor:'canceled',
              Cell: ({ original }) => {
                var label = original.canceled ? (
                  <Label bsStyle="danger">Odwołany</Label>
                ) : (
                  <Label bsStyle="success">Aktualny</Label>
                );

                return <h4 className='text-center' style={{margin:'auto'}}>{label}</h4>;
              }
            },
            IconButtonBarColumn({
              display: rowInfo => rowInfo.row.authorId !== 'null',
              iconButtons: [
                {
                  name: "ion-edit",
                  onClick: this.openEditWorkoutModal
                }
              ]
            })
          ]
          }
          ]}
          data={this.props.workouts}
        />
        </Dashboard.Content>
      </Dashboard>
    );
  }

  openEditWorkoutModal({id:workoutId}) {
    this.props.displayModal("EditWorkoutContent", { workoutId });
  }
}

export default connect(mapStateToProps, mapDispatchToProps)(
  TrainingsManagementDashboard
);
